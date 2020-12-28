<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class EmailParseController extends Controller
{
    public function store(Request $request)
    {
        Log::debug("Hellooooo");
        $thread_ts = $request->input('event.ts');
        $from_email = $request->input('event.files')[0]['from'][0]["address"];
        $old_email_original_address = $request->input('event.files')[0]['from'][0]["original"];
        $old_email_body = $request->input('event.files')[0]["plain_text"];
        $old_email_date_original = $request->input('event.files')[0]["headers"]["date"];
        $old_email_date_arr = explode(' ', $old_email_date_original);
        $old_email_date = $old_email_date_arr[0] . $old_email_date_arr[2] . " " . $old_email_date_arr[1] . " ". $old_email_date_arr[3] . " " . $old_email_date_arr[4];
        $email_id = $request->input('event.files')[0]["headers"]["message_id"];
        $reply_to_address = $request->input('event.files')[0]['to'][0]["address"];
        $subject = $request->input('event.files')[0]["subject"];
        if ($subject == null) {
            $subject = "";
        };
        Log::critical($subject);
        DB::insert('insert into emails (from_email_address, email_id, thread_value, reply_to_address, email_subject, old_email_body, old_email_date, old_email_original_address) values (?, ?, ?, ?, ?, ?, ?, ?)', [$from_email, $email_id, $thread_ts, $reply_to_address, $subject, $old_email_body, $old_email_date_original, $old_email_original_address]);
        return(response("", 204));
    }
}