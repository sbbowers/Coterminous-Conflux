<?php

class SqlException extends Exception {}
/*

  Class Sql - builds sql queries via a model heirarchy branded c,ql 
    (pronouced see-cue-el, purposefully close to the mispronounciation of sql's 'sequel')

  Use case: Select all purchases from your customer by email address 'bob@example.com'

  // Short example:
    select('purchase')['customer']->email('bob@example.com')

  Yes! c,ql knows about your schema!  This means that c,ql can automatically determine your 
  join clauses from the foreign keys in your schema.  Build your data correctly, and c,ql 
  will reward you greatly.  You can always be verbose with your statements if you're paranoid:

  // example
    select('purchase')
      ['customer']->on('purchase.customer_id', 'customer.id')
      ->email('bob')
 
  Joins are determined by the array accessor method, [], and can be chained in any order of 
  your criteria. ->on() directly modifies the join clause immediately preceeding it.  You can also use the 
  explicit join statement

    select('purchase')
      ->join('customer', purchase.customer_id, customer.id)
      ->email('bob')

  All methods (->example) other than sql reserved words are treated as column names.  The parameter 
  passed to the column name is always treated as an equivalence (=) parameter for scalars, an IS NUll for
  null values, or an IN() clause for arrays. To negate a clause use ->not($criteria)

    select('purchase')
      ->join('customer', purchase.customer_id, customer.id)
      ->not('email', 'bob@example.com')

  To select specific set of purchase by id:

    select('purchase')->id(array(1,2,3))

  Sometimes you need to compare a column to data that looks like a column name from your schema. c,ql
  is aggressive at matching your expression to an actual column. You should always escape your 
  user-defined strings using the e() function

    select('customer')->email(e('bob@example.com'))

  Sometimes you specify a complex expression that c,ql doesn't recognize (like stored procedures/functions,
  math).  c,ql will automatically escape the expression as if it were a string.  To prevent this, create 
  a verbatim expression with the v() function

  // example: update bob's next subscription date to be 30 days in the future
    update('customer')->set('next_sub_ts', v("now() + '30 days'::interval"))->email('bob@example.com')

  There are several ways to specify join criteria and generic criteria. Note that c,ql, unlike most ORMs,
  creates explicit joins.  This lets you utilize your database to create intelligent query plans when possible.
  Check out the following examples:

  // different ways to join
    select('purchase')[customer] // join purchase to customer, using foreign key criteria
    select('purchase')[customer]->on( <criteria> )  // join using your own criteria
    select('purchase')->join(customer, <criteria> ) // join using your own critiera, different variation

  // <criteria> follows the following variations. Use these for explicit join criteria, and generic criteria.
    ($snippet), 
    ($column_or_data, $column_or_data), 
    ($column_or_data, $operator, $column_or_data), 

  // different ways to add generic criteria:
    select('purchase')->id(1)                // column method definition, same as ->where('id', 1)
    select('purchase')->id('=', 1)           // column method definition, same as ->where('id', '=', 1)    
    select('purchase')->where('id=1')        // where clause variation 1 - build from a snippet
    select('purchase')->where('id', 1)       // where clause variation 2 - build an equivalence rule
    select('purchase')->where('id', '=', 1)  // where clause variation 3 - build an explicit rule
    select('purchase')->and('id', 1)         // ->where() is actuall and alias to ->and()
    select('purchase')->or('id', 1)          // generate or criteria. know that if ->or is the first
                                             // criteria, it won't really do anything. Only when concatenating 
                                             // other clauses

  c,ql lets you build queries that you don't have schema to as well.  You have to specify completely resolved
  columns though, and always provide ->on() criteria for your joins:

    select('purchase')['customer']->email('bob@example.com')

  You can chain as many where clauses as you need, the default behavior of where clauses is to use AND. You 
  also have OR at your disposal.  To correctly specify grouping, create additional sql criterions using sql():

  // example: get purchases by bob or nancy fletcher
    select('purchase')['customer']
      ->where(sql('name', 'Bob')->or('customer.name', 'Nancy'))
      ->and('last_name', 'Fletcher')

  Criteria clauses try hard to understand short-notation for your columns.  Each criteria has a context built 
  into it.  The tables defined closer to the criteria are tried first when resolving column names. Order your 
  criteria statements close to the tables that columns reference in order to correctly hint to c,ql what you want.

    select('purchase')
      ->created_ts('>', '2012-01-01')   // resolves to purchase.created_ts, because the current context is 'purchase'
      ['customer']
      ->created_ts('>', '2012-01-01')   // context is now customer, so this becomes 'customer.created_ts'


  There are several helper functions used in this documentation.  They are all quick accesses to the most 
  common operations.  You can always create an instance of the class too:
  
    // Verbose example
    $sql = new Sql();
    $sql->select('purchase')
      ->join('customer', purchase.customer_id, customer.id)
      ->where('customer.email','bob@example.com')

  Helper functions: 

    sql("customer.email='bob'") // generates a simple criterion, for usage with ->where()
    select(<table>) // returns an Sql instance with select criteria already specified
    delete(<table>) // returns an Sql instance with delete critera already specified
    update(<table>) // returns an Sql instance with update critera already specified
    e(<string>)     // returns an sql expression, escaped and string-quoted

*/

class Sql implements ArrayAccess
{
  protected
    $schema = null;
  public
    $commands = array(),
    $context = null;

  public function __construct($schema = null)
  {
    $this->schema = new Schema($schema);
    $this->context = new SqlContext();
  }

  // Provides a workaround for defining methods using php reserve words (eg "as")
  // prepend any desired method name with '__call_' to inject it into the class
  public function __call($method, $args)
  {
    static $commands = array('as', 'join', 'on', 'as', 'where', 'and', 'or', 'select', 'delete', 'update', 'order_by', 'group_by', 'having');
    if(in_array(strtolower($method), $commands))
      $this->__add_command(strtolower($method), $args);
    else
    {
      // Default behavior is a where clause
      array_unshift($args, $method);
      $this->__add_command('where', $args);
    }
    return $this;

  }

  public function offsetExists ($x) {}
  public function offsetSet ($x, $y) {}
  public function offsetUnset ($x) {}
  public function offsetGet ( $offset ) { return $this->join($offset); }
  public function __invoke (/* variable params */ ) 
  { 
    $args = func_get_args();
    $this->__add_command('where', $args); 
    return $this; 
  }

  protected function __add_command($command, $args)
  {
    static $prev_command = null;

    if($command == 'join')
      $this->context->set($args[0]);
    else if($prev_command == 'join' && $command == 'as')
      $this->context->set_alias($args[0]);

    $this->commands[] = array($this->context->get(), $command, $args);

    $prev_command = $command;
  }

  public function build($context = null)
  {
    $builder = new SqlBuilder($context ?: $this->context, $this->commands);
    return $builder->build();
  }
}

