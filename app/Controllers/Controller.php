<?php

namespace App\Controllers;

use PDO;
use App\Database\DB;

class Controller
{
    protected DB $db;

    public function __construct()
    {
        $this->db = DB::connect();
    }
}