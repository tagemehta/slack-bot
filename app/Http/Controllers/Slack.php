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
use App\Http\Controllers\EmailParseController;
use App\Http\Controllers\EmailSendController;
use App\Http\Controllers\ModalOpenController;
use App\Http\Controllers\ConfigController;
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
            Log::debug("Save Email");
            $c = new EmailParseController();
            $c->store($request);
        } else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "message_action" && json_decode($request->all()["payload"])->callback_id == "send_email" ) {
            Log::debug("Open Email View");
            $c = new ModalOpenController();
            $c->open_view($request);
        }
        else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "view_submission" && json_decode($request->all()["payload"])->view->callback_id == "send_email"){
            Log::debug("View Submit/Send Email");
            $c = new EmailSendController();
            $c->send($request);        
        }
        else if (null != $request->input('event.text')) {
            Log::debug("store channel");
            $c = new ConfigController();
            $c->store_channel($request);
        }
        else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "message_action" &&json_decode($request->all()["payload"])->callback_id == "configure" ) {
            Log::debug("open config view");
            $c = new ConfigController();
                $c->open_config_view($request);
        }
        else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->view->callback_id == "configure" && json_decode($request->all()["payload"])->type == "block_actions") {
            Log::debug("update config view");
            $c = new ConfigController();
            $c->update_config_view($request);
        }
        else if ($request->header('Content-Type') == 'application/x-www-form-urlencoded' && json_decode($request->all()["payload"])->type == "view_submission") {
            Log::debug("Close config view");
            $c = new ConfigController();
            $c->close_config_view($request);
        }
        else {
            Log::debug("no controller found");
            return(response("", 204));
        }
        return response("", 204);
        
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
