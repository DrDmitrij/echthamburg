<?php
/**
 * Created by PhpStorm.
 * User: traxes
 * Date: 22.03.19
 * Time: 11:00
 */

namespace App\Controller;


use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FrontendController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index($result = 0){
        if (!PHP_SESSION_ACTIVE)
            session_start();

        $pdo = $this->dbconnect();

        if (isset($_POST['fname'])) {
            $search = $_POST['fname'];

            $statement = $pdo->prepare("SELECT * FROM user WHERE first_name LIKE :key");
            $statement->bindValue(':key', '%' . $search . '%');
            $statement->execute();

            $result = $statement->fetchAll();
        }

        if (isset($_POST['lname'])) {
            $search = $_POST['lname'];

            $statement = $pdo->prepare("SELECT * FROM user WHERE last_name LIKE :key");
            $statement->bindValue(':key', '%' . $search . '%');
            $statement->execute();

            $result = $statement->fetchAll();
        }

        return $this->render('Frontend/search.html.twig', ['var' => $result]);
        $user = $this->getUser();

        if ($user)
            return $this->render('Frontend/search.html.twig');
        else
            return $this->redirect('/login');
    }

    /**
     * @Route("/login")
     */
    public function login(){
        $pdo = $this->dbconnect();
        $login = true;


        if(isset($_POST['email']) and isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $statement = $pdo->prepare("SELECT * FROM user WHERE email = :email");
            $result = $statement->execute(['email' => $email]);
            $user = $statement->fetch();

//            var_dump($user);die;

            if ($user !== false && $password === $user['password']) {
                $_SESSION['userid'] = $user['user_id'];
                return $this->index();
            } else {
                $login = false;
            }

        }

        return $this->render('Frontend/login.html.twig', ['login' => $login]);
    }

    /**
     * @param $userId
     * @Route("/show_user_{userId}")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showUser($userId)
    {

        $update = false;
        $pdo = $this->dbconnect();

        if (isset($_POST['fname']) or
            isset($_POST['fname']) or
            isset($_POST['role']) or
            isset($_POST['email'])) {

            $update = true;

            $statement = $pdo->prepare("UPDATE user SET 
                                                  first_name = ?,
                                                  last_name = ?, 
                                                  email = ?, 
                                                  role_id = ? 
                                                  WHERE user.user_id = ? ");

            $statement->execute([$_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['role'], $userId]);
        }

        $statement = $pdo->prepare("SELECT * FROM user WHERE user_id = ?");
        $statement->execute([$userId]);

        $user = $statement->fetch();

        return $this->render('Frontend/show_user.html.twig', [
            'userID' => $userId,
            'user' => $user,
            'update' => $update]);
    }


    public function dbconnect() {
        $pdo = new PDO('mysql:host=localhost;dbname=echthamburg', 'root', 'demo');
        return $pdo;

    }
}