<?php

    $courses = [];
    $query = "SELECT course_id,Name FROM courses ORDER by Name";
    if($result = $link->query($query)){
        while($course = $result->fetch_assoc()){
            $courses []= $course;
        }
    }
?>

<div class="list-group">
    <?php foreach($courses as $course) :?>
        <a href="<?=$_SERVER['PHP_SELF'].'?page=course&id='.$course['course_id']?>" class="list-group-item"><?= $course['Name'] ?></a>
    <?php endforeach ?>
</div>