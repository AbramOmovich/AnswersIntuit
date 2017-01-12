<?php
set_time_limit(0);

    function sql_error_check ($link){
        if (mysqli_error($link)){
            exit(mysqli_error($link));
        }
    }

    function get_data($name){
        $name = (string) $name;
        if(isset($_GET[$name])) return $_GET[$name];
        else return false;
    }

    function check_md5($str,$hash){
        $str_hash = md5($str);
        if (strcmp($str_hash,$hash) === 0) return true;
        else return false;
    }

    function get_combs (&$answers, $quest_str, $hash, $numb_of_answers = 1,$bFT = true ,$pos = 0, $to = 0){
        if ($numb_of_answers === count($answers['answ_id'])){
            for($k = 0; $k < count($answers['answ_id']); $k++){
                $quest_str .= '*a*'. $answers['answ_id'][$k];
            }
            if(check_md5($quest_str,$hash)){
                for($k = 0; $k < $numb_of_answers; $k++){
                    $answers['correct'] []= $answers['answ_id'][$k];
                }
            }
            return 0;
        }

        if($numb_of_answers === 1 && $bFT){
            for($k = 0; $k < count($answers['answ_id']); $k++){
                $temp_str = $quest_str.'*a*'.$answers['answ_id'][$k];
                if(check_md5($temp_str,$hash)){
                    $answers['correct'] [] = $answers['answ_id'][$k];
                    return 0;
                }
            }
        }
        else{
            if($bFT){
                $to = count($answers['answ_id']) - $numb_of_answers + 1;
                $bFT = false;
            }
            for($k = $pos; $k < $to; $k++){
                $temp_str = $quest_str.'*a*'.$answers['answ_id'][$k];
                if($to === count($answers['answ_id'])){
                    if(check_md5($temp_str,$hash)){
                        $vars = explode('*a*', $temp_str);
                        array_shift($vars);
                        array_shift($vars);
                        $answers ['correct'] = $vars;
                        return true;
                    }
                }
                else{
                    $flag = get_combs($answers,$temp_str,$hash,$numb_of_answers,$bFT,$k+1,$to+1);
                    if($flag === true ) return true;
                }
            }
        }
    }

    function course_serialize($dir){
        $QuestionPattern = "/\'(?<qst_id>\d+)\',(\'\d+\',){3}\"[\r\n]*?(?<qst_txt>[^a-z\d].*)[\r\n]*?\",\"(?<answ_hash>[\da-z]{32})\",\"[\da-z]{32}\",\[(?<avars>(\[(\'\d+\',){3}\"[\r\n]*?((.*?\n*?.*?)*?)[\r\n]*?\",\],){2,})\]/";

        if(file_exists($dir.'/'.'lectures.ser')){
            $lectures = file_get_contents($dir.'/'.'lectures.ser');
            $lectures = unserialize($lectures);
            foreach($lectures as $lecture_ord => $lecture_id){
                if(file_exists($dir.'/'.$lecture_id)){
                    if(file_exists($dir.'/'.$lecture_id.'_qs.ser') && file_exists($dir.'/'.$lecture_id.'_ans.ser')) continue;
                    $lecture = file_get_contents($dir.'/'.$lecture_id);
                    preg_match_all($QuestionPattern, $lecture, $questions);
                    for($i = 0 ; $i < 10; $i++){
                        unset($questions[$i]);
                    }
                    foreach($questions['qst_txt'] as &$txt){
                        $txt = trim($txt,"\t\n\r\s");
                    }

                    $NumbOfQuest = count($questions['qst_id']);
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
                    $h_questions = fopen($dir.'/'.$lecture_id.'_qs.ser','w+');
                    fwrite($h_questions,serialize($questions));
                    fclose($h_questions);

                    foreach($answers as &$answer){
                        foreach($answer['answ_txt'] as &$txt){
                            $txt = trim($txt,"\t\n\r\s");
                        }
                    }

                    $str_begin = 'asdc*a*';
                    for($i = 0; $i < $NumbOfQuest; $i++) {
                        $quest_str= $str_begin.$answers[$i]['quest_id'];
                        $numb_Of_answ = count($answers[$i]['answ_id']);
                        for($answ = 1 ; $answ <= $numb_Of_answ; $answ++){
                            get_combs($answers[$i],$quest_str,$questions['answ_hash'][$i],$answ);
                            if (array_key_exists('correct',$answers[$i])) break;
                        }
                    }
                    $h_answ = fopen($dir.'/'.$lecture_id.'_ans.ser','w+');
                    fwrite($h_answ,serialize($questions));
                    fclose($h_answ);
                }
            }
        }
    }
