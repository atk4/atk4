Agile Toolkit - Web UI Toolkit
====
Agile Toolkit is a Web UI framework and collection of usable widgets. It allows you to develop rich web applications by writing only PHP code. Agile Toolkit is inspired by Desktop GUI Toolkits and is a fully-object oriented development environment.

[![Build Status](https://travis-ci.org/atk4/atk4.png?branch=master)](https://travis-ci.org/atk4/atk4)

Overview
----
Agile Toolkit has introduced three new principles in web development:

 * A Complete UI solution for PHP developers
 * Unique integration between jQuery events and chains and PHP
 * Innovative Object Relational Manager with support for joins, sub-selects and expression abstraction.
 
All the features are delivered in a unique close-coupled environment - Similarly to Cocoa or Qt - all objects of Agile Toolkit are based off one common ancestor and are constructed under the guidance of the top-level Application object.

Installing
----
To start a new web application in Agile Toolkit, create a new folder anywhere in your web-root and execute there:

    curl -sS http://agiletoolkit.org/install | sh

This will install [Composer](http://getcomposer.org) and configure your secure installations. You can now preview your installation of Agile Toolkit:

    http://yoursite.com/atk4-project/public/

To modify your index page edit file `page/index.php`. 

Example
----
To help you understand some key principles of Agile Toolkit, copy the following example into `page/index.php` and place inside the init() method.

![Message to Romans](doc/message_to_romans.png)

Source:

    $form = $this->add('Form');
    $form->addField('line', 'subject')->validateNotNull();
    $form->addField('password','password');
    $form->addSubmit();
    
    if ($form->isSubmitted()) {
        $this->js()->univ()
            ->dialogOK('Hello World','Subject: '.$form['subject'])
            ->execute();
    }

Congratulations. You have now created a fully AJAX / PHP form fully protected from SQL / HTML / JS injection based on jQuery UI theme and Bootstrap-compatible 12-column flexible grid system.

Mini-Tutorial
----
Before sending you to look at [our website](http://agiletoolkit.org/) for additional information, here is a short tutorial on building a really powerful yet simple CRUD system.

#### Preparing SQL

1. Create a database in mysql.
2. Edit `config.php` in the folder where you installed Agile Toolkit with the following:
    
     <?php    
     $config['dsn'] = "mysql://root:secret@localhost/mydb";

3. `mkdir doc; cd doc; mkdir dbupdates; ln -s ../atk4/tools/update.sh .`
4. create file `doc/dbupdates/myproj-001.sql` with:

    create table user id int not null primary key auto_increment,    
    email varchar(255)  
    password varchar(255);
    
5. run `./update.sh` which will sequentially execute `dbupdate` scripts with create/alter's.

That's a preferred way to perform database migrations, although you may use some tools and shortcuts.

#### Creating Model

Model describes logical data source for your Views:

    <?php   
    class Model_User extends SQL_Model
    {
        public $table='user';
        
        function init()
        {
            parent::init();
            
            $this->addField('email')->sortable(true);
            $this->addField('password')->type('password');
        }
    }

Your model can be quite complex, contain joins, relations, subqueries, expressions, event handlers and custom actions, but for now that's enough.

#### Creating new page

Default routing mechanism of Agile Toolkit will look for corresponding pages inside page/ folder. You already have `index.php` page. Create `users.php` file in there:

    <?php
    class page_users extends Page 
    {
        function init()
        {
             parent::init();
             
             $this->add('CRUD')->setModel('User');
        }
    }

#### Updating Application

Your application class is located in `lib/Frontend.php`. Anything you put there will affect ALL of your application. Locate init() method of your application and at the end insert the following lines:

    $this->dbConnect();
    $this->add('Menu',null,'Menu')
        ->addMenuItem('index')
        ->addMenuItem('users')
        ;
        
        
#### Test!

You are done. Refresh your browser. You should now see that the menu is initialized with 2 items. Clicking on second page will take you to the "users" page with a CRUD for your user table.


For most web frameworks this is as far as you get, but with Agile Toolkit this is only a beginning. 

## Further Reading

 * [http://agiletoolkit.org/](http://agiletoolkit.org) - Official website
 * [http://stackoverflow.com/questions/tagged/atk4?sort=newest&pagesize=50](#atk4 on Stack Overflow) - Questions and Answers
 * [https://groups.google.com/forum/?fromgroups#!forum/agile-toolkit-devel](Discussion group on Google Groups)

## License

Agile Toolkit is distributed under Affero GNU Public License for personal, private and open-source projects. For commercial projects you should purchase an inexpensive life-time license.

** Your support will ensure the longevity of Agile Toolkit **

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/8fd43ffe5d4a0d14183ea27487362660 "githalytics.com")](http://githalytics.com/atk4/atk4)

