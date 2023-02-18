<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
class ModalOpenController extends Controller
{
    public function open_view (Request $request) {
            Log::info("shortcut event logic");
            $body = json_decode($request->all()["payload"]);
            
            $trigger_id_event = $body->trigger_id;
            $thread = $body->message->ts;
            $channel_id = $body->channel->id;
            $email_db = DB::select("select email_subject, old_email_body from emails where thread_value=?", [$thread]);
            $email_subject_initial = $email_db[0]->email_subject;
            $old_email_body = $email_db[0]->old_email_body;
            $team_id = $body->team->id;
            Log::debug($team_id);
            $bot_access_token = DB::select("select bot_token from slack_workspaces where team_id=?", [$team_id])[0]->bot_token;
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
                                    ],
                                    [
                                        "block_id" => "my_block_id",
                                        "type" => "input",
                                        "optional" => true,
                                        "label" => [
                                            "type" => "plain_text",
                                            "text" => "Select a channel to post the result on"
                                                                        ],
                                        "element" => [
                                            "action_id" => "my_action_id",
                                            "type" => "conversations_select",
                                            "response_url_enabled" => true,
                                            "default_to_current_conversation" => true
                                        ]
                                    ]
                                        ], //blocks end bracket
                            "submit" => [
                                "emoji" => true,
                                "text" => "Send Email",
                                "type" => "plain_text"
                            ],
                            "private_metadata" => "$thread,$channel_id"
                        ]
                    ] //json end bracket
                )
                 //aray end paranthesis
            );
            Log::info(print_r(json_decode($r->getBody()), true));
            
        }
}
