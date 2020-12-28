<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
class ConfigController extends Controller {
    public function store_channel(Request $request) {
        Log::debug("top level running");
        $channel_id = $request->input("event.channel");
        $team_id = $request->input("team_id");
        $configured = DB::select("select configured, config_message_sent from channels where channel_id=?", [$channel_id]);
        $configured = (array) $configured;
        Log::debug($configured);
        Log::debug((empty($configured) || $configured[0]->configured == 0 && $configured[0]->config_message_sent == 0));
        if (empty($configured) || ($configured[0]->configured == 0 && $configured[0]->config_message_sent == 0)) {
            if (empty($configured)) {
                DB::insert("insert into channels (configured, channel_id, slack_email_address, team_id, config_message_sent) values (?, ?, ?, ?, ?)", [false, $channel_id, null, $team_id, true]);
            };
            Log::debug("False");
            // $bot_access_token = DB::select("select user_token from slack_workspaces where team_id=?", [$team_id])[0]->user_token;
            $bot_access_token = env("SLACK_ACCESS_TOKEN");
            $client = new Client([]);
            Log::debug($bot_access_token);
            Log::debug($team_id);
            $req_body = array(
                "headers" => [
                    "Authorization" => "Bearer $bot_access_token",
                    "Content-type" => "application/json; charset=utf-8"
                ],
                "json" => [
                    "channel" => $channel_id,
                    "text" => "Click the three dots on this message and use the configure shortcut"
                ]
                );
            $r = $client->request("POST", "https://slack.com/api/chat.postMessage", $req_body);
            Log::info(print_r(json_decode($r->getBody()), true));
        }
        return response("", 204);
    }
    public function open_config_view(Request $request){
        $client = new Client([]);
        $body = json_decode($request->all()["payload"]);
        $channel_id = $body->channel->id;
        $trigger_id = $body->trigger_id;
        $mc_block = array(
            "trigger_id" => $trigger_id,
            "view" => [
                "type" => "modal",
                "callback_id" => "configure",
                "title" => [
                    "type" => "plain_text",
                    "text" => "Configure Helpline"
                ],
                "submit" => [
                    "type" => "plain_text",
                    "text" => "Save"
                ],
            "blocks" => array(
                array(
                    "type" => "section",
                    "text" => array(
                        "type" => "plain_text",
                        "text" => "Are you using a forwarding address",
                        "emoji" => true
                    ),
                ),
                array(
                    "type" => "actions",
                    "block_id" => "radio_buttons_group",
                    "elements" => array(
                        array(
                            "type" => "radio_buttons",
                            "options" => array(
                                array(
                                    "text" => array(
                                        "type" => "plain_text",
                                        "text" => "Yes",
                                        "emoji" => true
                                    ),
                                    "value" => "value-0"
                                ),
                                array(
                                    "text" => array(
                                        "type" => "plain_text",
                                        "text" => "No",
                                        "emoji" => true
                                    ),
                                    "value" => "value-1"
                                )
                            ),
                            "action_id" => "config_slack_forwarding"
                        )
                    )
                )
                        ),
                "private_metadata" => $channel_id
            ]
        );
        $client = new Client([
            "base_uri"=>"https://slack.com",
            "headers" => [
                "Content-Type" => "application/json; charset=utf-8",
                "Authorization" => "Bearer ". env("SLACK_ACCESS_TOKEN")
            ]
        ]); 
        $r = $client->request("POST", "/api/views.open", 
        ["json" => $mc_block]);
        return response("", 204);
    }
    public function update_config_view(Request $request) {
        $body = json_decode($request->all()["payload"]);
        if ($body->actions[0]->selected_option->value == "value-1") {
            $client = new Client([]);
            $body = json_decode($request->all()["payload"]);
            $trigger_id = $body->trigger_id;
            $channel_id = $body->view->private_metadata;
            $view_id = $body->view->id;
            $bot_access_token = env("SLACK_ACCESS_TOKEN");
            $request_body = array(
                "response_action" => "update",
                "view_id" => $view_id,
                "view" => array(
                    "type" => "modal",
                    "title" => array(
                        "type" => "plain_text",
                        "text" => "Configure Helpline"
                    ),
                    "submit" => [
                        "type" => "plain_text",
                        "text" => "Save"
                    ],
                    "blocks" => array(
                        array(
                            "block_id" => "slack_email_input",
                            "type" => "input",
                            "element" => array(
                                "type" => "plain_text_input",
                                "action_id" => "slack_email_address"
                            ),
                            "label" => array(
                                "type" => "plain_text",
                                "text" => "Enter your slack email address",
                                "emoji" => true
                            )
                        )
                            ),
                    "private_metadata" => $channel_id
                )
            );
            $r = $client->request("POST", "https://slack.com/api/views.update", array(
                "headers" => ["Authorization" => "Bearer $bot_access_token"],
                "json" => $request_body
            )); 
        } else {
        }
    }
    public function close_config_view(Request $request) {
        $body = json_decode($request->all()["payload"]);
        // $usesEmail = 
        $channel_id = $body->view->private_metadata;
        if (isset($body->view->state->values->radio_buttons_group)) {
            $usesEmail = $body->view->state->values->radio_buttons_group->config_slack_forwarding->selected_option->value;
            DB::table('channels')
                ->where("channel_id", $channel_id)
                -> update(["configured" => true]);
            Log::info("Marked no");
        } else {
            Log::debug(var_dump($channel_id));
            $email_address = $body->view->state->values->slack_email_input->slack_email_address->value;
            Log::info($email_address);
            DB::table('channels')
                ->where("channel_id", $channel_id)
                ->update(["slack_email_address" => $email_address, "configured" => true]);
        }
    }
}