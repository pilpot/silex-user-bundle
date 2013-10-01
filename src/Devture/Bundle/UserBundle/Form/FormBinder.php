<?php
namespace Devture\Bundle\UserBundle\Form;

use Devture\Bundle\UserBundle\Model\User;
use Devture\Bundle\UserBundle\Validator\UserValidator;
use Devture\Bundle\UserBundle\Helper\BlowfishPasswordEncoder;
use Devture\Bundle\SharedBundle\Form\SetterRequestBinder;
use Symfony\Component\HttpFoundation\Request;

class FormBinder extends SetterRequestBinder {

	private $validator;

	private $encoder;

	public function __construct(UserValidator $validator, BlowfishPasswordEncoder $encoder) {
		parent::__construct();
		$this->validator = $validator;
		$this->encoder = $encoder;
	}

	protected function doBindRequest(User $entity, Request $request, array $options = array()) {
		$whitelisted = array('username', 'email', 'name', 'roles');
		$this->bindWhitelisted($entity, $request->request->all(), $whitelisted);

		$password = $request->request->get('password');
		if ($password !== '') {
			if (strlen($password) > 4096) {
				$this->violations->add('password', 'user.validation.password_too_long');
			} else {
				$entity->setPassword($this->encoder->encodePassword($password));
			}
		}

		$this->violations->merge($this->validator->validate($entity, $options));
	}

}
