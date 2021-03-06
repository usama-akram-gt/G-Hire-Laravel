<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\User;
use App\Message;
use App\Events\messagesEvent;


class ContactsController extends Controller
{
    public function get()
    {
        // get all users who has a chat with the user and messages are unread
        $contacts = array();
        $contacts[] = Message::select('to',DB::raw('COUNT("to") as unread_count'))->where('from', '=', auth()->id())->where('read','=',false)->groupBy('to')->get();
        $contacts[] = Message::select('to',DB::raw('COUNT("to") as read_count'))->where('from', '=', auth()->id())->where('read','=',true)->groupBy('to')->get();

        //print_r(json_encode($contacts));
        //   get a collection of items where sender_id is the user who sent us a message
        // and messages_count is the number of unread messages we have from him
        
        $users = array();
        for ($i = 0; $i < count($contacts); $i++){
            foreach($contacts[$i] as $contact){
                $users[] = User::where('id', '=', $contact->to)->get();
            }   
        }
        /*        
        print_r('<br>');
        for ($i = 0; $i < count($users); $i++){
            foreach($users[$i] as $user){
                print_r($user->email . '<br>');
            }
        }

        $unread_users = array();
        for ($i = 0; $i < count($unread_contacts); $i++){
            foreach($unread_contacts[$i] as $unread_contact){
                //print_r($unread_contact->to . ' ' . $unread_contact->to_count . '<br>');
                $unread_users[] = User::where('id', '=', $unread_contact->to)->get();
            }
        }
        */
        //foreach(json_decode($contacts) as $contact){
          //  $users[] = User::where('id', '=', $contact->to)->get();
            //print_r(json_decode(User::where('id', '=', $contact->to)->get()));
        //}
        /*
        for ($i = 0; $i < count($read_users); $i++){
            foreach($read_users[$i] as $read_user){
                print_r($read_user->email . '<br>');
            }
        }
        
        for ($i = 0; $i < count($unread_users); $i++){
            foreach($unread_users[$i] as $unread_user){
                print_r($unread_user->email . '<br>');
            }
        }
        */
        // add an unread key to each contact with the count of unread messages
          
        //print_r(json_decode($contacts));
        
        $messages=array();
        for ($i = 0; $i < count($contacts); $i++){
            foreach($contacts[$i] as $contact){
                //print_r($unread_contact->to . ' ' . $unread_contact->to_count . '<br>');
                // mark all messages with the selected contact as read
                Message::where('from', auth()->id())->where('to', $contact->to)->update(['read' => true]);
                $id = $contact->to;
                // get all messages between the authenticated user and the selected user
                $messages[] = Message::where(function($q) use ($id) {
                    $q->where('from', auth()->id());
                    $q->where('to', $id);
                })->orWhere(function($q) use ($id) {
                    $q->where('from', $id);
                    $q->where('to', auth()->id());
                })
                ->get();
                //$message = json_encode($messages,JSON_NUMERIC_CHECK);
                break;
            }
        }
        /*
        for ($i = 0; $i < count($messages); $i++){
            foreach($messages[$i] as $message){
                print_r('<br>');
                print_r($message->to. '  ' .$message->text.'<br>');
            }
        }
        */

        /*
        for ($i = 0; $i < count($read_contacts); $i++){
            foreach($read_contacts[$i] as $read_contact){
                //print_r($unread_contact->to . ' ' . $unread_contact->to_count . '<br>');
                // mark all messages with the selected contact as read
                Message::where('from', auth()->id())->where('to', $read_contact->to)->update(['read' => true]);
                $id = $read_contact->to;
                // get all messages between the authenticated user and the selected user
                $messages[] = Message::where(function($q) use ($id) {
                    $q->where('from', auth()->id());
                    $q->where('to', $id);
                })->orWhere(function($q) use ($id) {
                    $q->where('from', $id);
                    $q->where('to', auth()->id());
                })
                ->get();
                print_r(json_encode($messages));
                break;
            }
        }
        */  
        
        //print_r(json_decode($contacts_count));
        //foreach(json_decode($contacts_count) as $contact_count){
          //  print_r($contact_count->to_count . '<br>');
        //} 
        $usertype = DB::table('users')->where('id','=',auth()->id())->get('usertype');
        if($usertype[0]->usertype == 'ProductOwner'){
            //check he has uploaded any project or not?
            $projects[] = DB::table('projects')->where('userid_fk','=',auth()->id())->get();

            //if he uploaded any project then check wether he accepts someone related to that project?
            for ($i = 0; $i < count($projects); $i++){
                foreach($projects[$i] as $project){
                    $ongoingprojects[] = DB::table('ongoingprojects')->where('project_id','=',$project->id)->get();
                }   
            }


            //now get every developer detail against each live project
            for ($i = 0; $i < count($ongoingprojects); $i++){
                foreach($ongoingprojects[$i] as $ongoingproject){
                    $dev_details[] = DB::table('users')->where('id','=',$ongoingproject->dev_id)->get();
                    $live_projects[] = DB::table('projects')->where('id','=',$ongoingproject->project_id)->get();
                }   
            }

            $total_invested = 0;
            for ($i = 0; $i < count($live_projects); $i++){
                foreach($live_projects[$i] as $live_project){
                    $budget = DB::table('projects')->where('id','=',$live_project->id)->get('budget');
                    $total_invested += $budget[0]->budget;
                }   
            }          

        }
        if($usertype[0]->usertype == 'Developer'){
            //check on how many he has applied via appliedprojects
            $projects[] = DB::table('appliedprojects')->where('dev_id','=',auth()->id())->get();

            //check how much are on-going
            for ($i = 0; $i < count($projects); $i++){
                foreach($projects[$i] as $project){
                    $ongoingprojects[] = DB::table('ongoingprojects')->where('project_id','=',$project->project_id)->where('dev_id','=',auth()->id())->get();
                }   
            }

            //now getting product-owner detials and projects details to show
            for ($i = 0; $i < count($ongoingprojects); $i++){
                foreach($ongoingprojects[$i] as $ongoingproject){
                    $prodO_details[] = DB::table('users')->where('id','=',$ongoingproject->prodO_id)->get();
                    $live_projects[] = DB::table('projects')->where('id','=',$ongoingproject->project_id)->get();
                }   
            }
        }
        return view('messages', compact('contacts','users','messages','live_projects'));
    }

    public function getMessagesFor($id)
    {
        // mark all messages with the selected contact as read
        Message::where('from', $id)->where('to', auth()->id())->update(['read' => true]);

        // get all messages between the authenticated user and the selected user
        $messages = Message::where(function($q) use ($id) {
            $q->where('from', auth()->id());
            $q->where('to', $id);
        })->orWhere(function($q) use ($id) {
            $q->where('from', $id);
            $q->where('to', auth()->id());
        })
        ->get();

        return response()->json($messages);
    }

    public function send(Request $request)
    {
        $message = Message::create([
            'from' => $request->contact_id,
            'to' => auth()->id(),
            'read' => false,
            'text' => $request->text
        ]);
        event(new messagesEvent($request->text,$request->contact_id));
        return response()->json(['message' => $request->text,'to' => $request->contact_id],200);
    }

    public function initailsend(Request $request,$id)
    {
        $message = Message::create([
            'from' => $id,
            'to' => auth()->id(),
            'read' => false,
            'text' => $request->text
        ]);
        event(new messagesEvent($request->text,$request->contact_id));
        return back();
    }
}
