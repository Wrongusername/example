<?

namespace common\components;
use common\models\lib\Operator;
use common\components\AbstractSearchRequest;
use common\components\AbstractOffer;
use common\components\AbstractClient;
use common\components\AbstractBooking;


/**
 * Интерфейс  адаптера поставщика услуг
 */
interface iServiceProvider
{
    public function __construct(Operator $operator);

    public function getOffers(AbstractSearchRequest $request, &$trace = []);

    public function getPaymentValue(AbstractOffer $offer, $options);

    public function getOfferByTid($tid);

    public function getProviderOrderData($orderId);

    public function approveOrder($orderId);

    public function validate(AbstractOffer $offer, AbstractClient $client, $options);

    /**
     * Оплата
     *
     * @param BookingParams
     *
     */
    public function doPayment(AbstractBooking $bookingParams);

}