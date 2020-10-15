<?php

use Codeception\Actor;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

   /**
    * Define custom actions here
    */

    /**
     * Получаем имя метода который выполняется в данный момент.
     *
     * @return string
     */
   public function getActionName(): string
   {
       return $this->getScenario()->current('name');
   }

    /**
     * Skip test
     *
     * @param string $message
     */
   public function skip(string $message): void
   {
       $this->getScenario()->skip($message);
   }

    /**
     * Incomplete test
     *
     * @param string $message
     */
    public function incomplete(string $message): void
    {
        $this->getScenario()->incomplete($message);
    }
}
