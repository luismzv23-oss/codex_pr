<?php

namespace App\Controllers;

class AuditController extends BaseController
{
    public function index()
    {
        return view('reports/audit', [
            'title' => 'Auditoria',
            'logs' => $this->repository->getAuditLogs(),
        ]);
    }
}
