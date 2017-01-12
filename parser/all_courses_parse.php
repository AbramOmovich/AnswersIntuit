<?php
    include '../inc/eVars.php';
    include '../inc/functions.php';


    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $arrCourseNames = [];
        if (is_dir('../'.COURSES_DIR) and is_dir('../'.COURSES_NAME_DIR)){
            $AllCourses = scandir('../'.COURSES_DIR);
            foreach($AllCourses as $key => &$course){
                if($key < 2 || mb_substr($course,0,1) === '.' || is_dir('../'.COURSES_DIR.'/'.$course)){
                    unset($AllCourses[$key]);
                    continue;
                }
                $course = pathinfo($course,PATHINFO_FILENAME);
                if(is_dir('../'.COURSES_NAME_DIR.'/'.$course)){
                    $index_file = '../'.COURSES_NAME_DIR.'/'.$course.'/'.'index.html';
                    if(file_exists($index_file)){
                        $str = file_get_contents($index_file);
                        $match=[];
                        preg_match_all($NamePattern,$str,$match);
                        for($i = 0; $i < 2; $i++){
                            unset ($match[$i]);
                        }
                        $arrCourseNames []=array('course_alias' => $course, 'course_name' => $match['course_name'][0]);
                    }
                }
            }

            $AllCourses = [];
            foreach($arrCourseNames as $course){
                $query = "SELECT * FROM courses WHERE  alias = '{$course['course_alias']}'";
                $result = $link->query($query);
                sql_error_check($link);
                if($result !== false ){
                   if($result->num_rows === 0){
                        $course['course_name'] = mysqli_escape_string($link, $course['course_name']);
                        $query = "INSERT INTO courses (course_id,name,alias) VALUES (NULL ,'{$course['course_name']}','{$course['course_alias']}')";
                        $link->query($query);
                        sql_error_check($link);
                        $AllCourses []=$course['course_alias'];
                    }
                    else {
                        $complete = $result->fetch_assoc();
                        if ( $complete['complete'] === '0') $AllCourses []=$course['course_alias'];
                    }
                }

            }
            header('Location: '.'./parser_start.php?'.http_build_query(array('courses' => $AllCourses)));
        }

    }

    $query = "select 'Ответов' as 'table_name', count(*) as 'rows' from answers union all select 'Вопросов' as 'table_name',count(*) as 'rows' from questions union all select 'Лекций' as 'table_name', count(*) as 'rows' from lectures union all select 'Курсов' as 'table_name', count(*) as 'rows' from courses";
    $amounts = [];
    if($result = $link->query($query)){
        while($amount = $result->fetch_assoc()){
            $amounts []= $amount;
        }
    }else sql_error_check($link);
    $link->close();
?>

    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Парсинг всех курсов</title>
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/sweetalert.css">
    </head>
    <body>
        <div class="col-lg-12" align="center">
            <h1>Парсинг всех курсов</h1>
            <form action="<?=$_SERVER['PHP_SELF']?>" method="post">
                <button type="submit" class="btn btn-default">Начать</button>
            </form>
            <div class="col-lg-3">
                <table class="table table-responsive">
                    <?php foreach($amounts as $amount):?>
                        <tr>
                            <td><h5><?=$amount['table_name']?></h5></td>
                            <td><h5><?=$amount['rows']?></h5></td>
                        </tr>
                    <?php endforeach?>
                </table>
            </div>
        </div>
    </body>
    </html>
