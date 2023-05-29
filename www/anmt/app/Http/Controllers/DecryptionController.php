<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

@include(app_path() . '/Includes/logging.php');

class DecryptionController extends Controller
{
    public function decrypt($encrypted_secret) {
      try {
        $plain_secret = Crypt::decryptString($encrypted_secret);
      } catch (DecryptException $e) {
        $error_msg = '';
      }
      return $plain_secret;
    }

    public function run() {
      $target = '';
      $action = "Decrypted credentials.";
      $user = "Unknown";
      $status = "Unknown";
      $variable_string = '';


      // Retrieve securely stored password.
      $secrets_path = storage_path('app/encrypted_secret.txt');
      $encrypted_secret = Storage::disk('local')->get($secrets_path);

      if ($encrypted_secret == 0) {
        $failure_msg = 'There are no credentials saved.';
        return redirect()->back()->with('failure', $secrets_path);
      }
      else {
        return redirect()->back()->with('failure', $secrets_path);
      }

      echo $encrypted_secret;

      $plain_secret = $this->decrypt($encrypted_secret);
      $credentials = json_encode($plain_secret);
 
      echo $credentials;

      return view('/update_topology')->with('credentials', $plain_secret);

      $action = "Updated topology map and inventory.";
      $target = "";
      $user = "";
      $status = "OK";
      $details = $command;

      create_log_record($action, $target, $user, $status, $details);

      echo $result->output();
      return redirect('/topology');
    }
}
?>
