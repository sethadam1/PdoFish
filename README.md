# PdoFish
An Active Record style wrapper for PHP and PDO

## Purpose
[PHP Active Record](http://www.phpactiverecord.org) was last updated in 2010, with the latest nightly release being in 2013. Because PHPAR appears to be abandoned, PdoFish was designed as an extremely lightweight alternative, which recreates some of the same conventions. 

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
```Model::deleteAll()``` - delete multiple rows  
```Model::deleteById()``` - delete by a column called "id"   
```Model::deleteByIds()``` - delete multiple rows matching criteria   
```Model::truncate()``` - truncate a table  

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
