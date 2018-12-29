<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
// use App\Leave;

class LeaveController extends Controller
{
    public function applyLeave(Request $request){

      $leave = new Leave([
          'type'      => $request->typeOfLeave,
          'startDate' => $request->startDate,
          'endDate'   => $request->endDate,
          'reliever'  => $request->reliever
      ]);

      $leave->save();

    }

    public function employees(Request $request){
      $employees = User::where('user_type', 'normal');
      return $employees;
    }

    public function leaveHistory($id){
      $history = Leave::where('id', $id)->get();
      return $history;
    }
}
