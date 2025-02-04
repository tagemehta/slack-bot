<?php

namespace App\Http\Controllers;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Mail;
use App\Mail\BaseEmail;


class EmailSendController extends Controller
{
    protected static function pg_array_parse($literal)
    {
        if ($literal == '') return;
        preg_match_all('/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i', $literal, $matches, PREG_SET_ORDER);
        $values = [];
        foreach ($matches as $match) {
            $values[] = $match[3] != '' ? stripcslashes($match[3]) : (strtolower($match[2]) == 'null' ? null : $match[2]);
        }
        return $values;
    }
    public function send(Request $request) {
        $body = json_decode($request->all()["payload"]);
        // Log::critical($body);
        // Log::critical(print_r($body, true));
        $exploded_pm = explode(",", $body->view->private_metadata);
        $thread = $exploded_pm[0];
        
        $channel_id = $exploded_pm[1];
        $user = $body->user->name;
        $messages = DB::select('select * from emails where thread_value = ?', [$thread]);
        Log::critical(empty($messages[0])); 
        if (empty($messages[0]) == false) {
            $old_email_date = $messages[0] ->old_email_date;
            $old_email_body = $messages[0] ->old_email_body;
            $old_email_original_address = $messages[0]->old_email_original_address;
            $from_email_address = $messages[0]->from_email_address;
            $channel_db = DB::table("channels")
                    ->join('slack_workspaces', 'channels.team_id', '=', 'slack_workspaces.team_id')
                    ->where('channel_id', '=', $channel_id )
                    ->get();
            
            if (!!$messages[0]->reply_to_address) {
                $reply_to = $messages[0]->reply_to_address;
                Log::debug("1");
            } else {
                Log::debug(print_r($channel_db, true));
                $reply_to = $channel_db[0]->slack_email_address;
                Log::debug("2");
            }
            $ccList = [];
            if (null != $messages[0]->cc) {
                $ccList = self::pg_array_parse($messages[0]->cc);
            }
            // Log::debug(print_r($channel_db[0], true));

            $subject = $messages[0]->email_subject;
            $email_id = $messages[0]->email_id;
            $email_subject = $body->view->state->values->email_subject_block->email_subject->value;
            $email_body = $body->view->state->values->email_body_block->email_body->value;
            
            Mail::to($from_email_address)
                ->cc($ccList)
                ->send(new BaseEmail(["body" => $email_body
            , "date" => $old_email_date, "old_email" => $old_email_original_address, "old_message" => $old_email_body
        ], $reply_to, $email_id, $email_subject));
        Log::debug("1");
        $confirmation_url = $body->response_urls[0]->response_url;
        $client = new Client([]);
        $request_body = array(
            "json" => [
                array(
                    "blocks" => array(
                        array(
                            "type" => "context",
                            "elements" => array(
                                array(
                                    "type" => "plain_text",
                                    "text" => "From Classrooms.cloud Helpine",
                                    "emoji" => true
                                )
                            )
                        ),
                        array(
                            "type" => "context",
                            "elements" => array(
                                array(
                                    "type" => "plain_text",
                                    "text" => "To $from_email_address",
                                    "emoji" => true
                                )
                            )
                        ),
                        array(
                            "type" => "section",
                            "text" => array(
                                "type" => "plain_text",
                                "text" => "$email_body",
                                "emoji" => true
                            )
                        )
                    )
                )
            ]
                            );
        
        $r = $client->request('POST', $confirmation_url, array("json"=> ["text" => "Email Sent to $from_email_address"]));

        $bot_access_token = DB::select("select bot_token from slack_workspaces where team_id=?", [$channel_db[0]->team_id])[0]->bot_token;
        $l = $client->request("POST", "https://slack.com/api/reactions.add", [
            "headers" => [
                "Authorization" => "Bearer $bot_access_token",
                "Content-Type" => "application/json"
            ],
            "json" => [
                "channel" => $channel_id,
                "timestamp" => $thread,
                "name" => "white_check_mark"

            ]
        ]);
        $c = $l = $client->request("POST", "https://slack.com/api/chat.postMessage", [
            "headers" => [
                "Authorization" => "Bearer $bot_access_token",
                "Content-Type" => "application/json"
            ],
            "json" => [
                "channel" => $channel_id,
                "thread_ts" => $thread,
                "text" => "From $user: $email_body"

            ]
        ]);

            return(response("", 204));
        };
        return(response("", 204));
        //cuduztvdutmrncme
    }
}
