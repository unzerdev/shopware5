{block name="frontend_checkout_confirm_heidelpay_frames_credit_card_test_data"}
    <div id= "heidelpay--credit-card-test-data">
        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_test_data_title"}
            <p>{s name='title'}{/s}</p>
        {/block}
        {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_test_data_table"}
            <table class="heidelpay--test-data-table">
                {block name="frontend_checkout_confirm_heidelpay_frames_credit_card_test_data_table_rows"}
                    <tr>
                        <th>{s name="column/cardType"}{/s}</th>
                        <th>{s name="column/brand"}{/s}</th>
                        <th>{s name="column/number"}{/s}</th>
                        <th>{s name="column/expiryDate"}{/s}</th>
                        <th>{s name="column/cvc"}{/s}</th>
                    </tr>
                    <tr>
                        <td>{s name="cardType/creditCard"}{/s}</td>
                        <td>Mastercard</td>
                        <td>5232050000010003</td>
                        <td>12/30</td>
                        <td>123</td>
                    </tr>
                    <tr>
                        <td>{s name="cardType/debitCard"}{/s}</td>
                        <td>Visa Electron</td>
                        <td>4012888888881881</td>
                        <td>12/30</td>
                        <td>123</td>
                    </tr>
                    <tr>
                        <td>{s name="cardType/rejection"}{/s}</td>
                        <td>Visa</td>
                        <td>4644400000308888</td>
                        <td>12/30</td>
                        <td>123</td>
                    </tr>
                {/block}
            </table>
        {/block}

    </div>
{/block}
