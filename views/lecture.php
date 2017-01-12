<?php if($lecture_id = get_data('id')):?>
    <?php
        $query = mysqli_escape_string($link, "SELECT q.question_id, q.text as qst_txt FROM lectures as l INNER JOIN questions as q USING (lecture_id) WHERE lecture_id ={$lecture_id}");
        $result = $link->query($query);
        sql_error_check($link);
        $questions = [];
        while ($question = $result->fetch_assoc()) {
            $questions []= $question;
        }

        foreach($questions as &$question){
            $query = "SELECT text as answ_text, correct FROM answers WHERE question_id={$question['question_id']}";
            $result = $link->query($query);
            sql_error_check($link);
            while ($answer = $result->fetch_assoc()) {
                $question['answers'] []= $answer;
            }
        }
    ?>
    <table class="table">
    <?php foreach($questions as &$question):?>
        <tr>
            <td><h4><?=$question['qst_txt']?></h4></td>
            <?php foreach($question['answers'] as $answer):?>
                <tr>
                    <td class="<?php if($answer['correct'] == '0') echo 'bg-danger'; else echo 'bg-success' ?>">
                        <p><?=$answer['answ_text'] ?></p>
                    </td>
                </tr>
                <?php ?>
            <?php endforeach?>
        </tr>
    <?php endforeach ?>
    </table>

<?php endif ?>