# Laravel Encryptable Fields

Allows to encrypt a database without creating a massive SQL dump 

## Install

Via Composer

``` bash
$ composer require tomlegkov/encryptable-fields
```

Then run dump-autoload to configure the classes

``` bash
$ composer dump-autoload
```

And then add the service provider in `config/app.php`:
``` php
TomLegkov\EncryptableFields\EncryptableFieldsServiceProvider::class,
```

And now you can publish the config:
``` bash
$ php artisan vendor:publish
```

## Usage

``` bash
# Create a new model, just as you would normally
$ php artisan make:model User --migration
```

#### Now your model might look something like this:
``` php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
# Import the encryptable trait
use TomLegkov\EncryptableFields\Encryptable;

class User extends Model {
	# Use the trait inside the model
    use Encryptable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    # Define inside $encryptable all of the fields that you want to have encrypted
    protected $encryptable = ['name', 'email'];
}
```

#### Now you can create a model just as you would normally:
``` php
$user = App\User::create([
	'name'		=> 'Tom Legkov'
	'email'		=> 'tom.legkov@outlook.com',
	'password'	=> Hash::make('123456')
]);
```

#### But the row in the database will be encrypted, something like this:
![Example Row](https://i.gyazo.com/79cd473d5e802176c4b814e547842b4c.png)

### Querying encrypted models
The library defines the `whereEncrypted` scope:
```php
App\User::whereEncrypted($column, $value)
```

Let's say I want to check emails for uniqueness during registration:
``` php
function emailExists($email){
	$user = App\User::whereEncrypted('email', $email)->first();
	return $user != null;
}
```

And after retrieving a model you can use it normally
``` php
$user = App\User::find(1);
$user->email; # tom.legkov@outlook.com
$user->name; # Tom Legkov
```

## Migrations
When defining a column that will be encrypted, specify it as a "string" with the length of 48 (unless you changed the length in the config), for example:
``` php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 48);
            $table->string('email', 48);
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::drop('users');
    }
}
```

## What about the database's size?
The database only stores short keys, whose length can be defined in the encryptable-fields.php config file. The default is 48 and it seems to work well with most types of apps.
The encryptions are stored inside Laravel's storage path, so when exporting/importing the database you also need to export/import those files.

## Credits

- Tom Legkov https://github.com/tomlegkov 

## License

Apache License 2.0. Please see [License File](LICENSE.md) for more information.