<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

session_destroy();
session_start();
set_flash('success', 'Berhasil logout.');
redirect('/auth/login.php');

