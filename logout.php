<?php
require_once 'config.php';
session_start_safe();
session_destroy();
redirect('login.php');
