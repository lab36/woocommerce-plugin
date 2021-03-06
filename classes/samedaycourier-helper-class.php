<?php

if (! defined( 'ABSPATH' ) ) {
	exit;
}

class SamedayCourierHelperClass
{
	/**
	 * @return array
	 */
	public static function getPackageTypeOptions()
	{
		return array(
			array(
				'name' => __("Parcel"),
				'value' => \Sameday\Objects\Types\PackageType::PARCEL
			),
			array(
				'name' => __("Envelope"),
				'value' => \Sameday\Objects\Types\PackageType::ENVELOPE
			),
			array(
				'name' => __("Large package"),
				'value' => \Sameday\Objects\Types\PackageType::LARGE
			)
		);
	}

	public static function getAwbPaymentTypeOptions()
	{
		return array(
			array(
				'name' => __("Client"),
				'value' => \Sameday\Objects\Types\AwbPaymentType::CLIENT
			)
		);
	}

	/**
	 * @return array
	 */
	public static function getDays()
	{
		return array(
			array(
				'position' => 7,
				'text' => __('Sunday'),
			),
			array(
				'position' => 1,
				'text' => __('Monday'),
			),
			array(
				'position' => 2,
				'text' => __('Tuesday')
			),
			array(
				'position' => 3,
				'text' => __('Wednesday')
			),
			array(
				'position' => 4,
				'text' => __('Thursday')
			),
			array(
				'position' => 5,
				'text' => __('Friday')
			),
			array(
				'position' => 6,
				'text' => __('Saturday')
			)
		);
	}

	/**
	 * @param $countryCode
	 * @param $stateCode
	 *
	 * @return string
	 */
	public static function convertStateCodeToName($countryCode, $stateCode)
	{
		return html_entity_decode(WC()->countries->get_states()[$countryCode][$stateCode]);
	}

	/**
	 * @param $inputs
	 *
	 * @return array
	 */
	public static function sanitizeInputs($inputs)
	{
		$sanitizedInputs = array();
		foreach ($inputs as $key => $val) {
			$sanitizedInputs[$key] = strip_tags($val);
		}

		return $sanitizedInputs;
	}

    /**
     * @return array|null
     */
    public static function getShippingMethodSameday($orderId)
    {
        $data = array();

        $shippingLines = wc_get_order($orderId)->get_data()['shipping_lines'];

        $serviceMethod = null;
        foreach ($shippingLines as $array) {
            $index = array_search($array, $shippingLines);
            $serviceMethod = $shippingLines[$index]->get_data()['method_id'];
        }

        if ($serviceMethod !== 'samedaycourier') {
            return null;
        }

        $awb = SamedayCourierQueryDb::getAwbForOrderId($orderId);

        if (!empty($awb)) {
            $data['awb_number'] = $awb->awb_number;
        }

        return $data;
    }

    /**
     * @param string $shippingMethodInput
     */
    public static function parseShippingMethodCode($shippingMethodInput)
    {
        $serviceCode = explode(":", $shippingMethodInput, 3);

        $serviceCode = isset($serviceCode[2]) ? $serviceCode[2] : null;

        return $serviceCode;
    }

    /**
     * @param array $errors
     *
     * @return string
     */
    public static function parseAwbErrors($errors)
    {
        $allErrors = array();
        foreach ($errors as $error) {
            foreach ($error['errors'] as $message) {
                $allErrors[] = implode('.', $error['key']) . ': ' . $message;
            }
        }

        return implode('<br/>', $allErrors);
    }

    /**
     * @param string $notice
     * @param string $notice_message
     * @param string $type
     * @param bool $dismissible
     *
     * @return void
     */
    public static function addFlashNotice($notice = "", $notice_message = "", $type = "warning", $dismissible = false)
    {
        update_option($notice, array(
                "message" => $notice_message,
                "type" => $type,
                "dismissible" => $dismissible
            )
        );
    }

    /**
     * @param $notice
     *
     * @return void
     */
    public static function showFlashNotice($notice)
    {
        $notices = get_option($notice);
        if (! empty($notices)) {
            self::printFlashNotice($notices['type'], $notices['message'], $notices['dismissible']);

            // After show flash message in page, remove it from db.
            delete_option($notice);
        }
    }

    /**
     * @param $type
     * @param $dismissible
     * @param $message
     *
     * @return void
     */
    public static function printFlashNotice($type, $message, $dismissible)
    {
        printf( '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
            $type,
            ($dismissible) ? "is-dismissible" : "",
            $message
        );
    }

    /**
     * @param $string
     *
     * @return string|string[]
     */
    public static function removeAccents($string)
    {
        $from = ['Ă', 'ă', 'Â', 'â', 'Î', 'î', 'Ș', 'ș', 'Ț', 'ț'];
        $to =   ['A', 'a', 'A', 'a', 'I', 'i', 'S', 's', 'T', 't'];

        return str_replace($from, $to, $string);
    }
}
