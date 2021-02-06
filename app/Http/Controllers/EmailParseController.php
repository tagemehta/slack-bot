<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class EmailParseController extends Controller
{
    protected static function to_pg_array($set) {
        settype($set, 'array'); // can be called with a scalar or array
        $result = array();
        foreach ($set as $t) {
            if (is_array($t)) {
                $result[] = to_pg_array($t);
            } else {
                $t = str_replace('"', '\\"', $t); // escape double quote
                if (! is_numeric($t)) // quote only non-numeric values
                    $t = '"' . $t . '"';
                $result[] = $t;
            }
        }
        return '{' . implode(",", $result) . '}'; // format
    }
    public function store(Request $request)
    {
        
        $body = $request->input('event.files');
        Log::debug($body);
        $thread_ts = $request->input('event.ts');
        if ($body[0]['from'][0]["address"] == "help@classrooms.cloud") {
            $from_email = $body[0]["headers"]["reply_to"];
        } else {
            $from_email = $body[0]['from'][0]["address"];
        }
        $ccList = [];
        if ($body[0]['cc']) {
            $ccArr = $body[0]['cc'];
            foreach($ccArr as $ccAddress) {
                array_push($ccList, $ccAddress["address"]);
            }
            
        }
        $fromArr = $body[0]['from'];
        array_shift($fromArr);
        foreach($fromArr as $ccAddress) {
            array_push($ccList, $ccAddress["address"]);
        }
        $old_email_original_address = $from_email;
        $old_email_body = $body[0]["plain_text"];
        $old_email_date_original = $body[0]["headers"]["date"];
        $old_email_date_arr = explode(' ', $old_email_date_original);
        $old_email_date = $old_email_date_arr[0] . $old_email_date_arr[2] . " " . $old_email_date_arr[1] . " ". $old_email_date_arr[3] . " " . $old_email_date_arr[4];
        $email_id = $body[0]["headers"]["message_id"];
        $reply_to_address = $body[0]['to'][0]["address"];
        $subject = $body[0]["subject"];
        if ($subject == null) {
            $subject = "";
        };
        Log::critical($subject);
        DB::insert('insert into emails (from_email_address, email_id, thread_value, reply_to_address, email_subject, old_email_body, old_email_date, old_email_original_address, cc) values (?, ?, ?, ?, ?, ?, ?, ?,?)', [$from_email, $email_id, $thread_ts, $reply_to_address, $subject, $old_email_body, $old_email_date_original, $old_email_original_address, self::to_pg_array($ccList)]);
        return(response("", 204));
    }
}