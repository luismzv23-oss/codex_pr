<?php

namespace App\Controllers;

class AuditController extends BaseController
{
    public function index()
    {
        $data = $this->repository->getAuditPage(max(1, (int) $this->request->getGet('page')));

        if ($this->request->isAJAX()) {
            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
                'html' => view('reports/_audit_listing', $data), 'pagination' => $data['pagination'],
            ]);
        }

        return view('reports/audit', ['title' => 'Auditoria'] + $data);
    }
}
