# PdoFish
An Active Record style wrapper for PHP and PDO

## Usage
In general, you'd include Pdofish/pdofish.php and then your model file. 

At that point, you would staticly call your class like so: 



//print an associative array  
```
$x = PdoFishExample::first(['conditions'=>['some_field=?', 'some_value']], PDO::FETCH_ASSOC);
print_r($x); 
```  

// print a row where primary key, in this case 'example_id' = 5  
```
$x = PdoFishExample::find_by_pk(5);
print_r($x);
```  

// print a single row matching SQL query  
```
$x = PdoFishExample::find_by_sql('select * from random_table where random_field=12');
print_r($x);
```  

// print a row where id = 5  
```
$x = PdoFishExample::find(5);
print_r($x); 
```

// print 5 rows of data from this query   
```
$x = PdoFishExample::all([
	'select'=>'field1, field2, field3',
	'from'=>'table t',
	'joins'=>'LEFT JOIN table2 t2 ON t.field1=t2.other_field',
	'conditions' => ['some_field=?', 'some_value'],
	'order'=>'third_field ASC',
	'limit'=>5
], PDO::FETCH_OBJ);
print_r($x);
```
