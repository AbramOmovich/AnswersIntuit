<?php if($course_id = get_data('id')) :?>
    <?php

        $query = mysqli_escape_string($link, "SELECT Name FROM courses WHERE course_id={$course_id}");
        $result = $link->query($query);
        sql_error_check($link);
        $course_name = $result->fetch_assoc()['Name'];

        $query = mysqli_escape_string($link, "SELECT lecture_ord,lecture_id FROM courses INNER JOIN lectures USING (course_id) WHERE course_id ={$course_id} ORDER BY lecture_ord");
        $result = $link->query($query);
        sql_error_check($link);
        $lectures = [];
        while ($lecture = $result->fetch_assoc()) {
            $lectures [] = $lecture;
        }
    ?>
    <h3>Курс: <?=$course_name?></h3>
    <h3>Выбирете лекцию</h3>
    <div class="list-group">
        <?php foreach($lectures as $lecture):?>
            <a href="<?=$_SERVER['PHP_SELF'].'?page=lecture&id='.$lecture['lecture_id']?>" class="list-group-item"><?= 'Лекция № '.$lecture['lecture_ord']?></a>
        <?php endforeach ?>
    </div>
<?php endif?>