<?php

namespace App\Controllers;

use Config\Database;

class DataTransferController extends BaseController
{
    /**
     * Tables available for export/import.
     * Excludes: users, auth_identities, auth_groups_users, auth_permissions_users,
     *           auth_logins, auth_remember_tokens, auth_token_logins, settings.
     */
    private function allowedTables(): array
    {
        return [
            'customers',
            'loan_applications',
            'loans',
            'installments',
            'payments',
            'amortization_systems',
            'collection_methods',
            'currencies',
            'notifications',
            'audit_logs',
        ];
    }

    public function index()
    {
        return view('data_transfer/index', [
            'title'  => 'Exportar / Importar datos',
            'tables' => $this->allowedTables(),
        ]);
    }

    // ── EXPORT ──────────────────────────────────────────────────────────

    public function export()
    {
        $table = (string) $this->request->getGet('table');
        $allowed = $this->allowedTables();

        if (! in_array($table, $allowed, true)) {
            return redirect()->back()->with('errors', ['Tabla no permitida para exportacion.']);
        }

        $db = Database::connect();

        if (! $db->tableExists($table)) {
            return redirect()->back()->with('errors', ["La tabla \"{$table}\" no existe en la base de datos."]);
        }

        $rows = $db->table($table)->get()->getResultArray();

        if ($rows === []) {
            return redirect()->back()->with('errors', ["La tabla \"{$table}\" no contiene registros."]);
        }

        $filename = $table . '_' . date('Ymd_His') . '.csv';
        $headers  = array_keys($rows[0]);

        $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->response->setHeader('Cache-Control', 'no-cache');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);

        return $this->response->send();
    }

    // ── IMPORT ──────────────────────────────────────────────────────────

    public function import()
    {
        $table = (string) $this->request->getPost('table');
        $allowed = $this->allowedTables();

        if (! in_array($table, $allowed, true)) {
            return redirect()->back()->with('errors', ['Tabla no permitida para importacion.']);
        }

        $file = $this->request->getFile('csv_file');

        if (! $file || ! $file->isValid() || $file->getExtension() !== 'csv') {
            return redirect()->back()->with('errors', ['Debes subir un archivo CSV valido.']);
        }

        $db = Database::connect();

        if (! $db->tableExists($table)) {
            return redirect()->back()->with('errors', ["La tabla \"{$table}\" no existe en la base de datos."]);
        }

        $handle = fopen($file->getTempName(), 'r');

        if ($handle === false) {
            return redirect()->back()->with('errors', ['No se pudo abrir el archivo CSV.']);
        }

        // skip BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 0, ';');

        if ($headers === false || $headers === []) {
            fclose($handle);

            return redirect()->back()->with('errors', ['El archivo CSV no contiene encabezados validos.']);
        }

        $headers = array_map('trim', $headers);

        $tableFields = $db->getFieldNames($table);
        $invalidCols = array_diff($headers, $tableFields);

        if ($invalidCols !== []) {
            fclose($handle);

            return redirect()->back()->with('errors', ['Columnas no validas en el CSV: ' . implode(', ', $invalidCols)]);
        }

        $imported = 0;
        $errors   = [];

        $db->transStart();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) !== count($headers)) {
                $errors[] = 'Fila ' . ($imported + 2) . ': cantidad de columnas incorrecta.';

                continue;
            }

            $data = array_combine($headers, $row);

            // Convert empty strings to null for nullable columns
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }

            try {
                $db->table($table)->replace($data);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Fila ' . ($imported + 2) . ': ' . $e->getMessage();
            }
        }

        fclose($handle);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('errors', ['La importacion fallo. Se revirtieron todos los cambios.']);
        }

        $message = "Se importaron {$imported} registros en la tabla \"{$table}\".";

        if ($errors !== []) {
            return redirect()->back()->with('message', $message)->with('errors', $errors);
        }

        return redirect()->back()->with('message', $message);
    }
}
