<?php
namespace Devture\Bundle\UserBundle\Helper;

use browserid\Verifier;
use Devture\Component\DBAL\Exception\NotFound;
use Devture\Component\Form\Helper\StringHelper;
use Devture\Bundle\UserBundle\Repository\UserRepositoryInterface;
use Devture\Bundle\UserBundle\Model\User;

class AuthHelper {

	private $repository;
	private $encoder;
	private $passwordTokenSalt;
	private $browserIdVerifier;

	public function __construct(UserRepositoryInterface $repository, PasswordEncoder $encoder, $passwordTokenSalt) {
		$this->repository = $repository;
		$this->encoder = $encoder;
		$this->passwordTokenSalt = $passwordTokenSalt;
	}

	public function setBrowserIdVerifier(Verifier $verifier) {
		$this->browserIdVerifier = $verifier;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return NULL|User
	 */
	public function authenticate($username, $password) {
		try {
			$user = $this->repository->findByUsername($username);
		} catch (NotFound $e) {
			return null;
		}
		if (!$this->isPasswordMatching($user, $password)) {
			return null;
		}
		return $user;
	}

	/**
	 * @param string $username
	 * @param string $passwordToken
	 * @return NULL|User
	 */
	public function authenticateWithToken($username, $passwordToken) {
		try {
			$user = $this->repository->findByUsername($username);
		} catch (NotFound $e) {
			return null;
		}
		if (!StringHelper::equals($this->createPasswordToken($user), $passwordToken)) {
			return null;
		}
		return $user;
	}

	/**
	 * @param string $assertion
	 * @return NULL|User
	 */
	public function authenticateWithBrowserIdAssertion($assertion) {
		try {
			$response = $this->browserIdVerifier->verify($assertion);
			if ($response->status === 'okay' && $response->email !== null) {
				return $this->repository->findByEmail($response->email);
			}
		} catch (\browserid\Exception $e) {

		} catch (NotFound $e) {

		}

		return null;
	}

	public function createPasswordToken(User $user) {
		return hash('sha256', $this->passwordTokenSalt . $user->getPassword());
	}

	private function isPasswordMatching(User $user, $password) {
		if (strlen($password) > 4096) {
			//Do not pass very long passwords to the encoder. Computing a hash might be slow.
			//Just reject them outright.
			return false;
		}
		return $this->encoder->isPasswordValid($password, $user->getPassword());
	}

}
