<?php
namespace C;

trait BaseTrait
{
	private $BaseTrait_init = null;

	// Trait constructor
	// Coterminous uses the semantic that Trait Constructors follow the <trait>::<trait> form 
	// rather than the <class>::<__construct> form.  This allows us to disambiguate constructors
	// for each trait and bootstrap them in with a single $this->BaseTrait() call
	protected function BaseTrait()
	{
		if($this->BaseTrait_init++)
			return;

		foreach(class_uses($this) as $class)
		{
			if(method_exists($class, $class))
				$this->$class();
		}
	}

}