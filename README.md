# PdoFish
An Active Record style wrapper for PHP and PDO

## Purpose
[PHP Active Record](http://www.phpactiverecord.org) was last updated in 2010, with the latest nightly release being in 2013. Because PHPAR appears to be abandoned, PdoFish was designed as an extremely lightweight alternative of a subset of the ActiveRecord syntax. The goal was to create a drop-in replacement of _some_ of the same conventions as PHPAR. 

The goal of this project was to recreate the _static_ methods that work with table models.  This project is not for everyone. Sloppy programming can break these functions. However, if you're already using PHPAR or wish to use an Active Record style DB interface in PHP, this may suit. 

We do not intend to recreate several conventions within PHPAR or all of the ActiveRecord conventions, such as inserting via ```new Model($data);``` or record updates via ```$model->save();```.  We also are not aiming to recreate ```Model::table()->x()``` functions.  

## Currently Supported Methods
```Model::raw()``` - execute raw SQL  
```Model::find_by_pk()``` - find a single row by primary key  
```Model::find()``` - find by a column called "id"  
```Model::all()``` - return all rows matching a query   
```Model::first()``` - returns the first row matching a query  
```Model::find_by_sql()``` - returns a single row matching a query  
```Model::find_all_by_sql()``` - returns all rows matching a query  
```Model::lastInsertId()``` - returns last insert id  
```Model::count()``` - return matching row count  
```Model::insert()``` - insert data  
```Model::update()``` - update field(s)  
```Model::delete()```  - delete a row  
```Model::deleteById()``` - delete by a column called "id"   
```Model::deleteMany()``` - delete multiple rows matching criteria   

#### Dynamic function names
```Model::find_by_[field]``` - find a single row by a specific column value  
```Model::find_all_by_[field]``` - find multiple rows where one column matches the given value  

#### The following methods must be called via the PdoFish class
```PdoFish::truncate($table)``` - truncate a table, must be called via PdoFish class  

## Usage
- Begin by opening ```credentials.php``` and setting your database connection information.  
- Upload the files to your web server.  
- Add any models in the ```models/``` directory, being sure to use the example to extend the ```PdoFish``` class and set the ```$table``` variable and ```id```, if your primary key is not already called ```id```. 
- Include Pdofish/PdoFish.php in your code and you should be ready to go. 

```  
require_once '/path/to/PdoFish/PdoFish.php';  //include_once() is also ok
```

At that point, you would statically call your class like so: 

```
//print an associative array  
$x = PdoFishModelName::first(['conditions'=>['some_field=?', 'some_value']], PDO::FETCH_ASSOC);
print_r($x); 
```  

```
// print a row where primary key, in this case 'example_id' = 5
$x = PdoFishModelName::find_by_pk(5);
print_r($x);
```  

```
// print a single row matching SQL query  
$x = PdoFishModelName::find_by_sql('select * from random_table where random_field=12');
print_r($x);
```  

```
// print a row where id = 5   
$x = PdoFishModelName::find(5);
print_r($x); 
```

```
// print 5 rows of data from this query   
$x = PdoFishPdoFishModelNameExample::all([
	'select'=>'field1, field2, field3',
	'from'=>'table t',
	'joins'=>'LEFT JOIN table2 t2 ON t.field1=t2.other_field',
	'conditions' => ['some_field=?', 'some_value'],
	'order'=>'third_field ASC',
	'limit'=>5
], PDO::FETCH_OBJ);
print_r($x);
```

## Arguments supported
The following arguments are supported in the PdoFish queries:  
```select``` - columns to select  
```from``` - table, or table and an alias _e.g. "prices p"_  
```joins``` - a string of joins in SQL syntax, _e.g. LEFT JOIN table2 on prices.field=table2.field_   
```conditions``` - an array of SQL, using ? placeholders, and arguments to be bound _e.g. ['year=? AND mood=?',2021,'happy']_   
```group``` - group by, using a field name  
```having``` - having, _e.g. 'count(x)>3'_  
```order``` - order by, _e.g. 'id DESC'  
```limit``` - a positive integer greater than 0  

## Credits
Some of this code has roots in the [David Carr](https://twitter.com/dcblogdev)'s [PDOWrapper](https://dcblog.dev/docs/pdo-wrapper) project. 
