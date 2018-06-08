<?php

namespace App\Http\Controllers;

use App\User;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
	public function balance(Request $request) {
		$validator = Validator::make($request->all(), [
			'user' => 'required|numeric|gte:1'
		]);
		try {
			if ($validator->fails()) {
				return $this->sendErrors($validator->errors()->all());
			}
		} catch (\InvalidArgumentException $e) {
			return $this->sendErrors($e->getMessage());
		}

		try {
			$user = User::findOrFail($request->input('user'));
		} catch (ModelNotFoundException $e) {
			return $this->sendErrors($e->getMessage());
		}

		return $this->out(['balance' => $user->balance]);
	}

	public function deposit(Request $request) {
		$backet = $request->json();
		$validator = Validator::make($backet->all(), [
			'user' => 'required|numeric|gte:1',
			'amount' => 'required|numeric|gte:0'
		]);
		try {
			if ($validator->fails()) {
				return $this->sendErrors($validator->errors()->all());
			}
		} catch (\InvalidArgumentException $e) {
			return $this->sendErrors($e->getMessage());
		}

		// lock for update should be used
		$uid = $backet->get('user');
		$user = User::firstOrNew(['id' => $uid]);
		$user->balance += $backet->get('amount');
		$user->save();
	}

	public function withdraw(Request $request) {
		$backet = $request->json();
		$validator = Validator::make($backet->all(), [
			'user' => 'required|numeric|gte:1',
			'amount' => 'required|numeric|gte:0'
		]);
		try {
			if ($validator->fails()) {
				return $this->sendErrors($validator->errors()->all());
			}
		} catch (\InvalidArgumentException $e) {
			return $this->sendErrors($e->getMessage());
		}

		// lock for update should be used
		try {
			$uid = $backet->get('user');
			$user = User::findOrFail($uid);
		} catch (ModelNotFoundException $e) {
			return $this->sendErrors($e->getMessage());
		}

		$amount = $backet->get('amount');
		if ($user->balance < $amount) {
			return $this->sendErrors('Not enough money: your balance should be greater on'.$amount - $user->balance);
		}
		$user->balance -= $amount;
		$user->save();
	}

	public function transfer(Request $request) {
		$backet = $request->json();
		$validator = Validator::make($backet->all(), [
			'from' => 'required|numeric|gte:1',
			'to' => 'required|numeric|gte:1',
			'amount' => 'required|numeric|gte:0'
		]);
		try {
			if ($validator->fails()) {
				return $this->sendErrors($validator->errors()->all());
			}
		} catch (\InvalidArgumentException $e) {
			return $this->sendErrors($e->getMessage());
		}

		$fromUid = $backet->get('from');
		$toUid = $backet->get('to');
		$amount = $backet->get('amount');
		if ($fromUid == $toUid) {
			return $this->sendErrors('Transfer works for 2 different users only!');
		}

		$uids = [$fromUid, $toUid];

		$users = User::whereIn('id', $uids)->get();
		if (!count($users)) {
			return $this->sendErrors('Users with given IDs not found');
		} else if (count($users) < 2) {
			$uid = array_diff($uids, [$users[0]->id]);
			return $this->sendErrors('User with id '.$uid[0].' not found');
		}

		$from = $fromUid == $users[0]->id ? $users[0] : $users[1];
		$to = $fromUid == $users[0]->id ? $users[1] : $users[0];

		if ($from->balance < $amount) {
			return $this->sendErrors('User has no enough money on his/her balance');
		}

		// should be locked for update
		DB::beginTransaction();
		$from->balance -= $amount;
		$from->save();
		$to->balance += $amount;
		$to->save();
		DB::commit();
	}

	private function sendErrors($errors) {
		$_errors = ['errors' => (array) $errors];
		return response(json_encode($_errors), 422);
	}

	private function out(array $data) {
		echo json_encode($data);
	}
}
