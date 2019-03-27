<?php
/**
 * Created by PhpStorm.
 * User: traxes
 * Date: 22.03.19
 * Time: 11:00
 */

namespace App\Controller;


use function mysql_xdevapi\getSession;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FrontendController extends AbstractController
{
    /**
     * @Route("/")
     * @param int $result
     * @return
     */
    public function index($result = 0){
        if ($this->checksession())
            return $this->redirect('/login');

        $pdo = $this->dbconnect();

        if (isset($_POST['submit'])) {
            $fname = $_POST['fname'];
            $lname = $_POST['lname'];
            $email = $_POST['email'];

            $sql = "SELECT * FROM user WHERE ";
            if (!$fname =='')
                $sql .= "first_name LIKE '". $fname ."%' ";

            if (!$lname =='')
                if (strlen($sql) <= 25)
                    $sql .= "last_name LIKE '". $lname ."%' ";
                else
                    $sql .= "AND last_name LIKE '". $lname ."%' ";

            if (!$email =='')
                if (strlen($sql) <= 25)
                    $sql .= "email LIKE '". $fname ."%' ";
                else
                    $sql .= "AND email LIKE '". $fname ."%' ";

            $statement = $pdo->prepare($sql);
            $statement->execute();

            $result = $statement->fetchAll();
        }

        if($_SESSION['userid']== 0)
            $session = false;
        else
            $session = true;

        return $this->render('Frontend/search.html.twig', ['var' => $result, 'session' => $session]);
    }

    /**
     * @Route("/login", name="login")
     * @param bool $logout
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function login($logout = false){

        $this->checksession();
        $login = true;

        if(isset($_POST['email']) and isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            if ($email === 'admin@test.de' && $password === 'demo') {
                $_SESSION['userid'] = 1;
                return $this->redirect('/');
            } else {
                $login = false;
            }
        }

        if($_SESSION['userid']== 0)
            $session = false;
        else
            $session = true;

        return $this->render('Frontend/login.html.twig', ['login' => $login, 'logout' => $logout, 'session' => $session]);
    }

    /**
     * @param $userId
     * @Route("/show_user_{userId}")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showUser($userId)
    {
        if ($this->checksession())
            return $this->redirect('/login');
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
                                                  date_of_birth =?,
                                                  role_id = ? 
                                                  WHERE user.user_id = ? ");

            $statement->execute([$_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['birthday'], $_POST['role'], $userId]);
        }

        $sql = "SELECT * FROM user WHERE user_id = ?";
        $statement = $pdo->prepare($sql);
        $statement->execute([$userId]);

        $user = $statement->fetch();

        $sql = "SELECT game.game_name
                                            FROM user 
                                            INNER JOIN game ON user.user_id = game.user_id
                                            WHERE user.user_id = ? ";
        $gameCreated = $this->userinfo($sql, $userId);

        $sql = "SELECT game.game_name, gametest.date, event.event_name, location.location_name, location.street, location.number, location.postcode, location.city
                FROM user
                INNER JOIN gametest ON user.user_id = gametest.user_id
                INNER JOIN game ON gametest.game_id = game.game_id
                INNER JOIN event ON gametest.event_id = event.event_id
                INNER JOIN location ON event.location_id = location.location_id
                WHERE user.user_id = ?";
        $gameTested = $this->userinfo($sql, $userId);

        $sql = "SELECT booking.date, shop.shop_name, location.location_name, location.street, location.number, location.postcode, location.city, event.event_name, event.start_date, event.end_date
                FROM user
                INNER JOIN booking ON user.user_id = booking.user_id
                INNER JOIN shop ON booking.shop_id = shop.shop_id
                INNER JOIN location ON shop.location_id = location.location_id
                INNER JOIN event ON booking.event_id = event.event_id
                WHERE user.user_id =?";
        $booking = $this->userinfo($sql, $userId);

        if($_SESSION['userid']== 0)
            $session = false;
        else
            $session = true;

        return $this->render('Frontend/show_user.html.twig', [
            'userID' => $userId,
            'user' => $user,
            'update' => $update,
            'session' => $session,
            'gameCreated' => $gameCreated,
            'gameTested' => $gameTested,
            'booking'=> $booking]);
    }

    /**
     * @Route("/logout")
     */
    public function logout(){
        if(isset($_SESSION))
            session_destroy();

        return $this->redirect('/login');
    }

    public function dbconnect() {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=echtHamburg', 'root', 'demo');
        return $pdo;
    }

    public function checksession(){
        if(!isset($_SESSION))
        {
            session_start();
        }
//        if (!PHP_SESSION_ACTIVE) {
//            session_start();
//            $_SESSION['userid'] = 0;
//        }
        $request = Request::createFromGlobals();
        $route =$request->getPathInfo();

        if (!isset($_SESSION['userid']))
            $_SESSION['userid'] = 0;

        if (($route !== '/login') and ($_SESSION['userid'] === 0))
            return true;
    }

    public function userinfo($sql, $userId){
        $pdo = $this->dbconnect();
        $statement = $pdo->prepare($sql);
        $statement->execute([$userId]);

        return  $statement->fetchall();
    }

    /**
     * @Route("/delete_{userid}")
     * @param $userid
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteuser($userid){
        $pdo = $this->dbconnect();

        $statement = $pdo->prepare("UPDATE booking SET user_id = 2 WHERE user_id = ? ");
        $statement->execute([$userid]);

        $statement = $pdo->prepare("UPDATE game SET user_id = 2 WHERE user_id = ? ");
        $statement->execute([$userid]);

        $statement = $pdo->prepare("UPDATE gametest SET user_id = 2 WHERE user_id = ? ");
        $statement->execute([$userid]);

        $statement = $pdo->prepare("UPDATE gametest SET user_id = 2 WHERE user_id = ? ");
        $statement->execute([$userid]);

        $statement = $pdo->prepare("DELETE FROM user WHERE user_id = ? ");
        $statement->execute([$userid]);

        return $this->redirect('/');
    }
}
