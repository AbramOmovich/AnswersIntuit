<?php
    include '../inc/eVars.php';
    include '../inc/functions.php';

    $courses = get_data('courses');
    if($courses){
        ob_end_clean();
        header("Connection: close");
        ignore_user_abort(true); 
        ob_start();
        echo '<link rel="stylesheet" href="css/bootstrap.min.css">
                <div class="col-lg-3" align="center">
                <h4>Обрабртка начата</h4>
                <div class="list-group">
                    <a href="/" class="list-group-item navbar-brand">На главную</a>
                </div>
             </div>
             ';
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush();
        flush();
        
        
        // Do processing here
        foreach($courses as $course_alias){

            $course_file = '../'.COURSES_DIR.'/'.$course_alias.'.pm';
            $course_dir =  '../'.COURSES_DIR.'/'.$course_alias;

            //Reading my %lecture to string
            $str = file_get_contents($course_file);
            $pattern = "/my\s+%lecture\s+=\s+\(([\n\t\s]*(\d)+[\n\t\s]*=>[\n\t\s]*(\d)+,)+/";
            preg_match($pattern,$str,$my_lecture);
            unset($str);
            $my_lecture = $my_lecture[0];

            //Making array lect_ord_numb => lecture_id
            $pattern = "/(?<lect_ord>(\d){1,5})[\n\t\s]*=>[\n\t\s]*(?<lect_id>(\d){1,5})+/";
            preg_match_all($pattern,$my_lecture,$lectures);
            for($i = 0; $i < 5; $i++) unset ($lectures[$i]);
            $lectures = array_combine($lectures['lect_ord'],$lectures['lect_id']);

            //Inserting lectures IDs into db
            $query = "SELECT course_id FROM courses WHERE alias = '{$course_alias}'";
            $result = $link->query($query);
            if($result !== false && $result->num_rows === 1){
                $CourseID = $result->fetch_assoc();
                $CourseID = $CourseID['course_id'];
                foreach($lectures as $lecture_ord => $lecture_id){
                    $query = "SELECT * FROM lectures WHERE lecture_id='{$lecture_id}'";
                    $result = $link->query($query);
                    if($result->num_rows === 0){
                        $query = "INSERT INTO lectures (lecture_id,lecture_ord,course_id) VALUES ('{$lecture_id}','{$lecture_ord}','{$CourseID}')";
                        $link->query($query);
                    }
                }
            }
            else if($result->num_rows === 0) continue;

            //Reading test table and breaking it into files in @course_name\@lecture_id
            $h_course_file = fopen($course_file,"r");
            if (!file_exists($course_dir)){
                mkdir($course_dir);
            }

            foreach($lectures as $lecture_ord => $lecture_id){
                while (!feof($h_course_file)){
                    $string = fgetss($h_course_file);
                    if (strpos($string,$lecture_id." => ") !== false){
                        if(!file_exists($course_dir."/".$lecture_id)){
                            $h_lecture = fopen($course_dir."/".$lecture_id,"w+");
                            fwrite($h_lecture,$string);
                            do{
                                $string = fgetss($h_course_file);
                                if (strpos($string,"],],],],],],]")) break;
                                fwrite($h_lecture,$string);
                            }while(!feof($h_course_file));
                            fwrite($h_lecture,$string);
                            fclose($h_lecture);
                        }
                        $lecture_id_full_file_name = $course_dir.'/'.$lecture_id;

                        $QuestionPattern = "/\'(?<qst_id>\d+)\',(\'\d+\',){3}\"[\r\n]*?(?<qst_txt>[^a-z\d].*)[\r\n]*?\",\"(?<answ_hash>[\da-z]{32})\",\"[\da-z]{32}\",\[(?<avars>(\[(\'\d+\',){3}\"[\r\n]*?((.*?\n*?.*?)*?)[\r\n]*?\",\],){2,})\]/";
                        $lecture_file_str = file_get_contents($lecture_id_full_file_name);

                        preg_match_all($QuestionPattern, $lecture_file_str, $questions);
                        unlink($lecture_id_full_file_name);

                        for($i = 0 ; $i < 10; $i++){
                            unset($questions[$i]);
                        }
                        foreach($questions['qst_txt'] as &$txt){
                            $txt = trim($txt,"\t\n\r\s");
                        }
                        $NumbOfQuest = count($questions['qst_id']);

                        /*Inserting questions
                         *   $questions = [
                         *      qst_id    => [n],
                         *       qst_txt   => [n],
                         *      answ_hash => [n],
                         *  ]
                         */
                        for($i = 0; $i < $NumbOfQuest; $i++){
                            $query = "SELECT * FROM questions WHERE question_id='{$questions['qst_id'][$i]}'";
                            $result = $link->query($query);
                            if($result !== false && $result->num_rows === 0){
                                $questions['qst_txt'][$i]=mysqli_escape_string($link,$questions['qst_txt'][$i]);
                                $query = "INSERT INTO questions (question_id,text,answ_hash,lecture_id) VALUES ('{$questions['qst_id'][$i]}','{$questions['qst_txt'][$i]}','{$questions['answ_hash'][$i]}','{$lecture_id}' )";
                                $link->query($query);
                            }
                        }

                        $answers = [];
                        $AnswerPattern = "/(\[\'(?<answ_id>\d+)\',\'(?<quest_id>\d+)\',\'\d+\',\"[\r\n]*?(?<answ_txt>(.*?\n*?.*?)*?)[\r\n]*?\",\],)/";
                        foreach($questions['avars'] as $avar){
                            preg_match_all($AnswerPattern,$avar,$answer);
                            for($i = 0 ; $i < 6; $i++){
                                unset($answer[$i]);
                            }
                            $answer['quest_id'] =  $answer['quest_id'][0];
                            $answers []= $answer;
                        }
                        unset($questions['avars']);
                        foreach($answers as &$answer){
                            foreach($answer['answ_txt'] as &$txt){
                                $txt = trim($txt,"\t\n\r\s");
                            }
                        }

                        /*
                         * answer = [
                         *      answ_id  => [k],
                         *      quest_id => '',
                         *      answ_txt => [k],
                         *      correct  => [m],
                         * ]
                         * */
                        $str_begin = 'asdc*a*';
                        for($i = 0; $i < $NumbOfQuest; $i++) {
                            $quest_str= $str_begin.$answers[$i]['quest_id'];
                            $numb_Of_answ = count($answers[$i]['answ_id']);
                            for($answ = 1 ; $answ <= $numb_Of_answ; $answ++){
                                get_combs($answers[$i],$quest_str,$questions['answ_hash'][$i],$answ);
                                if (array_key_exists('correct',$answers[$i])) break;
                            }
                        }


                        foreach($answers as &$answer){
                            //is question exists
                            $query = "SELECT * FROM questions WHERE question_id='{$answer['quest_id']}'";
                            $result = $link->query($query);
                            if($result !== false && $result->num_rows === 1){
                                $NumbOfAnswers = count($answer['answ_id']);
                                for($i = 0; $i < $NumbOfAnswers; $i++){
                                    $answer['answ_txt'][$i] = mysqli_escape_string($link,$answer['answ_txt'][$i]);

                                    //is answer exists
                                    $query = "SELECT * FROM answers WHERE answer_id={$answer['answ_id'][$i]}";
                                    $result = $link->query($query);
                                    if($result !== false && $result->num_rows === 0){

                                        if(in_array($answer['answ_id'][$i],$answer['correct'])){
                                            $query = "INSERT INTO answers(answer_id,question_id,text,correct) VALUES ({$answer['answ_id'][$i]},{$answer['quest_id']},'{$answer['answ_txt'][$i]}',1)";
                                        }
                                        else{
                                            $query = "INSERT INTO answers(answer_id,question_id,text) VALUES ({$answer['answ_id'][$i]},{$answer['quest_id']},'{$answer['answ_txt'][$i]}')";
                                        }
                                        $link->query($query);
                                    }
                                }
                            }
                        }
                    }
                }
                rewind($h_course_file);
            }
            fclose($h_course_file);
            if (file_exists($course_dir)){
                rmdir($course_dir);
            }
            $query = "UPDATE courses SET complete = 1 WHERE alias = '{$course_alias}'";
            $link->query($query);
        }
        $link->close();

        sleep(30);
    }else{
        header('Location: /');
    }