<?php

declare(strict_types=1);

namespace App\Controllers;

final class NatalController extends Controller
{
    public function index(): void
    {
        $this->view('natal/stub', [
            'title' => 'Carta natal — próximamente',
            'pageClass' => 'page-natal',
            'activeNav' => 'cartas',
        ]);
    }
}
