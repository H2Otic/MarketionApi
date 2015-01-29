<?php

class Marketion_Api_Class {

    private $_login = '';
    private $_pass  = '';
    private $_ch;
    private $_session_id;
    static $api_url = 'http://www.marketion.ru/app/api.php';

    public function __construct()
    {
        $this->_ch = curl_init(self::$api_url);
        curl_setopt($this->_ch, CURLOPT_URL, self::$api_url);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($this->_ch, CURLOPT_HEADER, 0);
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, 1);
    }

    /**
     * Авторизация
     * @param string $login
     * @param string $pass
     * @return bool
     */
    public function authorise($login = '', $pass = '')
    {
        if ($login) $this->_login = $login;
        if ($pass) $this->_pass = $pass;

        $params = array(
            'Command'        => 'User.Login',
            'ResponseFormat' => 'JSON',
            'Username'       => $this->_login,
            'Password'       => $this->_pass
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);

        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1) {
                $this->_session_id = $json->SessionID;
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Списки подписчиков
     * @return array|bool
     */
    public function getLists()
    {
        $params = array(
            'Command'        => 'Lists.Get',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'OrderField'     => 'name',
            'OrderType'      => 'ASC'
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return array(
                    'total' => $json->TotalListCount,
                    'lists' => $json->Lists
                );
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Получение всей информации о списке
     * @param int $list_id
     * @return array|bool
     */
    public function getList($list_id)
    {
        $params = array(
            'Command'        => 'List.Get',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'ListID'         => $list_id
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return array(
                    'list' => $json->List
                );
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Создание списка подписчиков
     * @param $list_name
     * @return bool
     */
    public function createList($list_name)
    {
        $params = array(
            'Command'            => 'List.Create',
            'ResponseFormat'     => 'JSON',
            'SessionID'          => $this->_session_id,
            'SubscriberListName' => $list_name
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return TRUE;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Удаление списка(ов) подписчиков
     * @param array $lists
     * @return bool
     */
    public function deleteList($lists)
    {
        $lists = implode(',', $lists);

        $params = array(
            'Command'        => 'Lists.Delete',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'Lists'          => $lists
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return TRUE;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Получение кастмных полей списка
     * @param int $list_id
     * @return array|bool
     */
    public function getCustomListFields($list_id)
    {
        $params = array(
            'Command'          => 'CustomFields.Get',
            'ResponseFormat'   => 'JSON',
            'SessionID'        => $this->_session_id,
            'OrderField'       => 'name',
            'OrderType'        => 'ASC',
            'SubscriberListID' => $list_id
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                /*return array(
                    //'total'  => $json->TotalCustomFields,
                    'fields' => $json->CustomFields
                );*/
                return $json;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Подписка на рассылку
     * @param int $list_id
     * @param string $email
     * @param string $fio
     * @param string $phone
     * @return bool|string
     */
    public function subscribe($list_id, $email, $fio = '', $phone = '')
    {
        $params = array(
            'Command'         => 'Subscriber.Subscribe',
            'ResponseFormat'  => 'JSON',
            'SessionID'       => $this->_session_id,
            'IPAddress'       => $_SERVER['REMOTE_ADDR'],
            'ListID'          => $list_id,
            'EmailAddress'    => $email,
            'CustomField1301' => $phone,
            'CustomField1300' => $fio
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1) {
                return 1;
            } else {
                $err_code = $json->ErrorCode;
                switch ($err_code) {
                    case 2:
                        return 'Email адрес не указан';
                        break;

                    case 5:
                        return 'Email адрес указан неверно';
                        break;

                    case 9:
                        return 'Данный адрес уже подписан';
                        break;

                    default :
                        return 'Произошла неизвестная ошибка! Попробуйте позднее';
                        break;
                }
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Отписка от рассылки
     * @param int $list_id
     * @param string $email
     * @return bool|string
     */
    public function unsubscribe($list_id, $email)
    {
        $params = array(
            'Command'        => 'Subscriber.Unsubscribe',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'ListID'         => $list_id,
            'EmailAddress'   => $email
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1) {
                return TRUE;
            } else {
                $err_code = $json->ErrorCode;
                switch ($err_code) {
                    case 7:
                        return 'Подписчик не найден';
                        break;

                    case 9:
                        return 'Данный подписчик уже отписался';
                        break;

                    default :
                        return 'Произошла неизвестная ошибка! Попробуйте позднее';
                        break;
                }
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Создание компании - рассылки
     * @param string $compain_name
     * @return bool
     */
    public function createCompain($compain_name)
    {
        $params = array(
            'Command'        => 'Campaign.Create',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'CampaignName'   => $compain_name
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return $json->CampaignID;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * @param int $compain_id
     * @param int $email_id
     * @param int $list_id
     * @param bool $send_later
     * @param int $send_date
     * @param int $send_time
     * @param int $send_max
     * @return bool
     */
    public function updateCompain($compain_id, $email_id, $list_id, $send_later = FALSE, $send_date = 0, $send_time = 0, $send_max = 1)
    {
        if (is_array($list_id)) {
            $arr = array();
            foreach ($list_id as $id) {
                $arr[] = "$id :0";
            }
            $lists_str = implode(',', $arr);
        } else {
            $lists_str = "$list_id: 0";
        }

        $params = array(
            'Command'                   => 'Campaign.Update',
            'ResponseFormat'            => 'JSON',
            'SessionID'                 => $this->_session_id,
            'CampaignID'                => $compain_id,
            'CampaignStatus'            => 'Sending',
            'RelEmailID'                => $email_id,
            'ScheduleType'              => 'Immediate',
            'RecipientListsAndSegments' => $lists_str
        );

        if ($send_later) {
            $params['ScheduleType']               = 'Future';
            $params['SendDate']                   = $send_date;
            $params['SendTime']                   = $send_time;
            $params['SendTimeZone']               = '(GMT+04:00) Moscow';
            $params['ScheduleRecSendMaxInstance'] = $send_max;
        }

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        $json = json_decode($result);
        if ($json->Success == 1)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Создание письма
     * @return bool
     */
    public function createEmail()
    {
        $params = array(
            'Command'        => 'Email.Create',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1) {
                return $json->EmailID;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Настройка письма
     * @param int $email_id
     * @param string $from_name
     * @param string $from_email
     * @param string $subject
     * @param string $plain
     * @param string $html
     * @return bool
     */
    public function updateEmail($email_id, $from_name, $from_email, $subject, $plain, $html)
    {
        $replacement = '<table width="600" cellspacing="10" cellpadding="0" align="center" border="0">
                            <tr>
                                <td>
                                    <span style="color: #a9a9a9; font-size: 11px;">Вы получили это письмо, потому что являетесь клиентом нашей компании. Если Ваш адрес был добавлен по ошибке, пожалуйста, воспользуйтесь ссылкой <a href="%Link:Unsubscribe%"> отписаться</a>."</span>
                                </td>
                            </tr>
                        </table></body>';
        $html = preg_replace('/<\/body>/i', $replacement, $html);
        $plain .= "\n%Link:Unsubscribe%\n\n";

        $params = array(
            'Command'        => 'Email.Update',
            'ResponseFormat' => 'JSON',
            'ValidateScope'  => 'Campaign',
            'SessionID'      => $this->_session_id,
            'EmailID'        => $email_id,
            'FromName'       => $from_name,
            'FromEmail'      => $from_email,
            'Mode'           => 'Empty',
            'Subject'        => $subject,
            'PlainContent'   => $plain,
            'HTMLContent'    => $html,
            'ImageEmbedding' => 'Enabled'
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($json = json_decode($result)) {
            if ($json->Success == 1)
                return TRUE;
            else
                return FALSE;
        } else {
            return FALSE;
        }

    }

    /**
     * Персонализация письма тегами (отправка либо по списку ли бо по тегам)
     * @param int $list_id
     * @return bool
     */
    public function personilizeEmail($list_id)
    {
        $params = array(
            'Command'        => 'Email.PersonalizationTags',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'ListID'         => $list_id,
            'Scope'          => 'Subscriber'
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);
        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return TRUE;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Отправка пробного письма
     * @param int $email_id
     * @param string $email_address
     * @param int $list_id
     * @param int $compain_id
     * @return bool
     */
    public function emailPreview($email_id, $email_address, $list_id, $compain_id)
    {
        $params = array(
            'Command'        => 'Email.EmailPreview',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'EmailID'        => $email_id,
            'EmailAddress'   => $email_address,
            'ListID'         => $list_id,
            'CampaignID'     => $compain_id
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);
        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return TRUE;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    /**
     * Проверка письма спам тестом
     * @param int $email_id
     * @return array|bool
     */
    public function spamTest($email_id)
    {
        $params = array(
            'Command'        => 'Email.SpamTest',
            'ResponseFormat' => 'JSON',
            'SessionID'      => $this->_session_id,
            'EmailID'        => $email_id
        );

        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($this->_ch);

        if ($result) {
            $json = json_decode($result);
            if ($json->Success == 1)
                return $json->TestResults;
            else
                return FALSE;
        } else {
            return FALSE;
        }
    }

    private function _buildQuery($params = array())
    {
        $url = self::$api_url;

        if ($params) {
            foreach ($params as $k => $v) {
                $url .= '&' . $k . '=' . $v;
            }
        }

        return $url;
    }

}

?>
