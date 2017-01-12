<?php
    set_time_limit(0);

    const COURSES_DIR = 'course';
    const COURSES_NAME_DIR = 'course_names';
    const SQL_HOST = 'localhost';
    const SQL_USER = 'root';
    const SQL_PASS = '';
    const DB_NAME  = 'intuit';



    $link = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASS, DB_NAME);

    $NamePattern = "/<span class=\"course_title\">(?<course_name>.*)<\/span>/";

    $page = (isset($_GET['page'])) ? strip_tags($_GET['page']) : index;