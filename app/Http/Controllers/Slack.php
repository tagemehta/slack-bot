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

class Slack extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return('hi');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->input('type') == 'url_verification') {
            
            return response()->json(
                [
                    "challenge" => $request->input("challenge")
                ]
                )
                            ->header('Content Type', 'Application/json');
            
        } else if ($request->input('event.files.0.filetype') == "email"){
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
        } else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "message_action") {
            Log::info("shortcut event logic");
            $body = json_decode($request->all()["payload"]);
            $access_token=env('SLACK_ACCESS_TOKEN');
            $trigger_id_event = $body->trigger_id;
            $thread = $body->message->ts;
            $subject_db = DB::select("select email_subject from emails where thread_value=?", [$thread]);
            $email_subject_initial = $subject_db[0]->email_subject;
            $team_id = $body->team->id;
            $bot_access_token = DB::select("select user_token from slack_workspaces where team_id=?", [$team_id])[0]->user_token;
            Log::critical($bot_access_token);
            $client = new Client([
                "base_uri"=>"https://slack.com",
                "headers" => [
                    "Content-Type" => "application/json; charset=utf-8",
                    "Authorization" => "Bearer ". $bot_access_token
                ]
            ]); 
            $r = $client->request("POST", "/api/views.open", array (
                    'headers' => [],
                    'json' => [
                        "trigger_id" => $trigger_id_event,
                        "view" => [
                            "type" => "modal",
                            "callback_id" => "send_email",
                            "title" => [
                                "type" => "plain_text",
                                "text" => "Send Email with Slack"
                            ],
                            "blocks" => [
                                [
                                    "block_id" => "email_subject_block",
                                    "element" => [
                                        "action_id" => "email_subject",
                                        "type" => "plain_text_input",
                                        "placeholder" => [
                                            "type" => "plain_text",
                                            "text" => "Subject"
                                        ],
                                        "initial_value" => "Re: " . $email_subject_initial
                                    ],
                                    "type" => "input",
                                    "label" => [
                                        "type" => "plain_text",
                                        "text" => "Email Subject"
                                    ]
                                ],
                                [
                                    "block_id" => "email_body_block",
                                    "element" => [
                                        "action_id" => "email_body",
                                        "type" => "plain_text_input",
                                        "multiline" => true,
                                        "placeholder" => [
                                            "type" => "plain_text",
                                            "text" => "Write your email here"
                                            ],
                                        ],
                                    "type" => "input",
                                    "label" => [
                                        "type" => "plain_text",
                                        "text" => "Email Body"
                                    ]
                                ]
                                        ], //blocks end bracket
                            "submit" => [
                                "emoji" => true,
                                "text" => "Send Email",
                                "type" => "plain_text"
                            ],
                            "private_metadata" => $thread,
                        ]
                    ] //json end bracket
                )
                 //aray end paranthesis
            );
            Log::debug(session('user_token'));
            Log::debug(print_r(json_decode($r->getBody()), true));
        }
        else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "view_submission"){
            $body = json_decode($request->all()["payload"]);
            Log::critical(print_r($body, true));
            $thread = $body->view->private_metadata;
            $messages = DB::select('select * from emails where thread_value = ?', [$thread]);
            // Log::critical($messages);
            if (empty($messages[0]) == false) {
                $old_email_date = $messages[0] ->old_email_date;
                $old_email_body = $messages[0] ->old_email_body;
                $old_email_original_address = $messages[0] ->old_email_original_address;
                $from_email_address = $messages[0]->from_email_address;
                $reply_to = $messages[0]->reply_to_address;
                $subject = $messages[0]->email_subject;
                $email_id = $messages[0]->email_id;
                $email_subject = $body->view->state->values->email_subject_block->email_subject->value;
                $email_body = $body->view->state->values->email_body_block->email_body->value;
                Mail::to($from_email_address)->send(new BaseEmail(["body" => $email_body, "date" => $old_email_date, "old_email" => $old_email_original_address, "old_message" => $old_email_body], $reply_to, $email_id, $email_subject));
                return(response("", 204));
            };
            return(response("", 204));
            //cuduztvdutmrncme
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
