<?php
namespace Devture\Bundle\UserBundle\Repository;

interface UserRepositoryInterface {

	public function findByUsername($username);

}