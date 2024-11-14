<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required']
        ]);

        $username = $request->input('username');
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $username = preg_replace('/[^0-9]/', '', $request->input('username'));
            // Check if phone number starts with '07' and has a length of 10
            if (substr($username, 0, 2) == '07' && strlen($username) == 10) {
                // Add '25' before the username number
                $username = '25' . $username;
            }

            if (!(substr($username, 0, 4) == '2507' && strlen($username) == 12)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid phone number. Please check your phone number.',
                    'result' => null,
                ]);
            }
        }
        $user = User::withTrashed()->with('shops.spareParts')->where('email', $username)->orWhere('phone', $username)->first();

        if ($user) {
            if ($user->deleted_at != NULL) {
                // User exists and is not deleted
                return response()->json([
                    'status' => false,
                    'message' => 'Account have been deleted. Contact the app manager for more info.',
                    'result' => null,
                ]);
            } else if ($user->confirmation_token != NULL) {
                $response = [
                    'status' => false,
                    'message' => 'Un verified account. Register again.',
                    'error' => null,
                ];
            } else if ($user->active == 'no') {
                $response = [
                    'status' => false,
                    'message' => 'Account is inactive. Please contact support for assistance.',
                    'error' => null,
                ];
            } elseif (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('my-app-token')->plainTextToken;
                $response = [
                    'status' => true,
                    'message' => 'User login successfully',
                    'user' => $user,
                    'token' => $token
                ];
            } else {
                $response = [
                    'status' => false,
                    'message' => 'Incorrect password. Please try again.',
                    'errors' => [
                        "password" => false
                    ]
                ];
            }
        } else {
            $response = [
                'status' => false,
                'message' => 'Account not found. Please check your phone number.',
                'errors' => [
                    "phone" => false,
                    "email" => false
                ]
            ];
        }


        return response($response, 201);
    }

    /**
     * Get all users based on role.
     * Order by requested order if not passed DESC.
     * Paginate if passed paginate true by default don't paginate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $role = $request->input('role');
            $order = $request->input('order', 'desc');
            $paginate = $request->input('paginate', true);

            $query = User::query();

            // Apply filtering based on role if provided
            if ($request->has('role')) {
                $query->where('role', $role);
            }

            // Apply filtering based on created_at date range if provided
            if ($request->has('date_from') && $request->has('date_to')) {
                $dateFrom = $request->input('date_from');
                $dateTo = $request->input('date_to');

                $query->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            }

            $users = $paginate ? $query->orderBy('created_at', $order)->paginate() : $query->orderBy('created_at', $order)->get();

            return response()->json([
                'status' => true,
                'message' => 'Users retrieved successfully.',
                'result' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users.',
                'result' => $e,
            ]);
        }
    }

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|unique:users,phone',
                'email' => 'nullable|email|unique:users,email',
                'first_name' => 'required',
                'last_name' => 'required',
                'password' => 'required|confirmed',
                'role' => 'required|in:vehicle_owner,vendor,admin',
            ]);

            $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
            $email = $request->input('phone');
            // Check if phone number starts with '07' and has a length of 10
            if (substr($phone, 0, 2) == '07' && strlen($phone) == 10) {
                // Add '25' before the phone number
                $phone = '25' . $phone;
            }

            if (!(substr($phone, 0, 4) == '2507' && strlen($phone) == 12)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid phone number. Please check your phone number.',
                    'result' => null,
                ]);
            }

            // Check if user with phone number already exists
            $user = User::withTrashed()->with('shops.spareParts')->where('email', $email)->orWhere('phone', $phone)->first();

            if ($user) {
                if ($user->deleted_at != NULL) {
                    // User exists and is not deleted
                    return response()->json([
                        'status' => false,
                        'message' => 'Account have been deleted. Contact the app manager for more info.',
                        'result' => null,
                    ]);
                } else if ($user->confirmation_token == NULL) {
                    // User already registered but not confirmed
                    // generate and send new confirmation code
                    $confirmation_code = rand(100000, 999999);
                    $confirmation_token = Hash::make($confirmation_code);

                    $user->update([
                        'first_name' => $request->input('first_name'),
                        'last_name' => $request->input('last_name'),
                        'phone' => $phone,
                        'email' => $request->input('email'),
                        'password' => Hash::make($request->input('password')),
                        'role' => $request->input('role'),
                        'active' => 'yes',
                        // 'confirmation_token' => '$2y$12$2JH1DMtTrJAnq.NV6baYt.KTjIICz4Z5FGwsrtVtk9z2qNBC0E5gy'
                    ]);

                    $message = 'Your confirmation code is ' . $confirmation_code . '.';
                    $confirmation_sms = $this->send_message($phone, $message);

                    return response()->json([
                        'status' => true,
                        'message' => 'Confirmation code was re-sent. Confirm account',
                        'user' => $user,
                        'result' => $confirmation_sms,
                    ]);
                } else {
                    // User already registered
                    return response()->json([
                        'status' => false,
                        'message' => 'Phone is already used. Contact the App manager if you didn\'t use it.',
                        'result' => null,
                    ]);
                }
            } else {
                // User not found
                // generate and send new confirmation code
                $confirmation_code = rand(100000, 999999);
                $confirmation_token = Hash::make($confirmation_code);

                $user = User::create([
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'phone' => $phone,
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'role' => $request->input('role'),
                    'active' => 'yes',
                    // 'confirmation_token' => '$2y$12$2JH1DMtTrJAnq.NV6baYt.KTjIICz4Z5FGwsrtVtk9z2qNBC0E5gy'
                ]);

                // Send confirmation code via SMS
                $message = 'Your confirmation code is ' . $confirmation_code . '.';
                $confirmation_sms = $this->send_message($phone, $message);

                $token = $user->createToken('my-app-token')->plainTextToken;
                return response()->json([
                    'status' => true,
                    'message' => 'User registered successfully.',
                    'user' => $user,
                    'token' => $token,
                    'result' => $confirmation_sms
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'result' => $e,
            ]);
        }
    }

    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        try {
            if ($request->user()->role != 'admin' && $request->user()->id != $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can delete a user.',
                    'error' => 'Unauthorized',
                ]);
            }
            $request->validate([
                'phone' => 'required|unique:users,phone,' . $user->id,
                'email' => 'nullable|email',
                'first_name' => 'required',
                'last_name' => 'required',
                'password' => 'nullable',
                'role' => 'required|in:vehicle_owner,vendor,admin',
            ]);

            $user->update([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'role' => $request->input('role'),
                'active' => $request->input('active'),
                'update_by' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User updated successfully.',
                'result' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(request $request, User $user)
    {
        try {
            if ($request->user()->role != 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can delete a user.',
                    'error' => 'Unauthorized',
                ]);
            }
            $user->update(['deleted_by' => $request->user()->id]);
            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully.',
                'result' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user.',
                'error' => $e,
            ]);
        }
    }

    /**
     * Confirm a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function confirmUser(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'confirmation_code' => 'required|string',
            ]);

            $phone = $request->input('phone');
            $confirmation_code = $request->input('confirmation_code');

            $user = User::where('phone', $phone)->first();
            if ($user) {
                if ($user->confirmation_token === null) {
                    // Confirmation token is null, indicating user is already confirmed
                    $user->tokens()->where('tokenable_id', $user->id)->delete();
                    $token = $user->createToken('my-app-token')->plainTextToken;
                    return response()->json([
                        'status' => false,
                        'message' => 'User is already confirmed.',
                        'user' => $user,
                        'token' => $token
                    ]);
                } else if (Hash::check($confirmation_code, $user->confirmation_token)) {
                    // Confirmation code matches
                    $token = $user->createToken('my-app-token')->plainTextToken;
                    $user->update(['confirmation_token' => null]);

                    return response()->json([
                        'status' => true,
                        'message' => 'User confirmed successfully.',
                        'user' => $user,
                        'token' => $token
                    ]);
                } else {
                    // Confirmation code does not match
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid Verification code.'
                    ]);
                }
            } else {
                // User not found
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to confirm user.',
                'errors' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send forget password email to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forgetPassword(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string'
            ]);

            $phone = preg_replace('/[^0-9]/', '', $request->input('phone'));
            // Check if phone number starts with '07' and has a length of 10
            if (substr($phone, 0, 2) == '07' && strlen($phone) == 10) {
                // Add '25' before the phone number
                $phone = '25' . $phone;
            }

            if (!(substr($phone, 0, 4) == '2507' && strlen($phone) == 12)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid phone number. Please check your phone number.',
                    'result' => null,
                ]);
            }

            $user = User::withTrashed()->where('phone', $phone)->first();
            if ($user) {
                if ($user->deleted_at != NULL) {
                    // User exists and is not deleted
                    return response()->json([
                        'status' => false,
                        'message' => 'Account have been deleted. Contact the app manager for more info.',
                        'result' => null,
                    ]);
                } else if ($user->confirmation_token != NULL) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Un verified account. Register again.'
                    ]);
                } else if ($user->active == 'No') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Account is inactive. Please contact support for assistance.',
                        'errors' => [
                            "phone" => false,
                            "password" => false
                        ]
                    ]);
                } else {
                    // Reset password
                    // Generate new password recovery code
                    $password = rand(100000, 999999);
                    $remember_token = Hash::make($password);

                    $user->update([
                        'password' => $remember_token,
                        'remember_token' => $remember_token
                    ]);

                    $message = 'Your reset password on your Ohereza account is ' . $password . '. Do not share this with anyone.';
                    $confirmation_sms = $this->send_message($phone, $message);

                    return response()->json([
                        'status' => false,
                        'message' => 'New password was sent to phone ' . $phone . '.',
                        'result' => $confirmation_sms,
                    ]);
                }
            } else {
                // User not found
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send forget password email.',
                'result' => null,
            ]);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        $user = User::where('id', $request->user()->id)->first();
        $user->tokens()->where('tokenable_id', $request->user()->id)->delete();
        $response = [
            'status' => true,
            'message' => "Logged Out!"
        ];
        return response()->json($response);
    }

    /**
     * Send message to user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function send_message($phone, $message)
    {
        $sender = 'SPAREPARTSCONNECT';
        $params = array(
            'sender'      => $sender,
            'content'     => $message,
            'msisdn'      => $phone,
            'username'    => 'bulksms',
            'password'    => 'bulksms345',
        );

        $ch = curl_init();
        $url = 'http://164.92.112.235:9090/sendSmsHandler/sendSimpleSms';

        // Set Curl options
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
        ));

        // Execute the request
        $output = curl_exec($ch);

        // Check for Curl errors
        if ($output === false) {
            return "Curl error: " . curl_error($ch);
        }

        // Close Curl handle
        curl_close($ch);

        // Return API response
        return $output;
    }
}
