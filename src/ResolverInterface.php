<?php namespace LordMonoxide\Phi;

interface ResolverInterface {
  /**
   * Gets or creates an instance of an alias, or returns null to allow the next Resolver to execute
   * 
   * @param   string  $alias      An alias (eg. `db.helper`), or a real class or interface name
   * @param   array   $arguments  The arguments to pass to the binding
   * 
   * @returns object|null An instance of `$alias`'s binding, or null to allow the next Resolver to execute
   */
  public function make($alias, array $arguments = []);
}
