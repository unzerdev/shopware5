{block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_test_data"}
    <div id= "unzer-payment--credit-card-test-data">
        {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_test_data_title"}
            <p>{s name='title'}{/s}</p>
        {/block}
        {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_test_data_table"}
            <table class="unzer-payment--test-data-table">
                {block name="frontend_checkout_confirm_unzer_payment_frames_credit_card_test_data_table_rows"}
                    <tr>
                        <th>{s name="column/cardType"}{/s}</th>
                        <th>{s name="column/brand"}{/s}</th>
                        <th>{s name="column/number"}{/s}</th>
                        <th>{s name="column/expiryDate"}{/s}</th>
                        <th>{s name="column/cvc"}{/s}</th>
                        <th>{s name="column/3dpass"}{/s}</th>
                    </tr>
                    <tr>
                        <td>{s name="cardType/creditCard"}{/s}</td>
                        <td>Mastercard</td>
                        <td>5232050000010003</td>
                        <td>{s name="column/expiryDateString"}{/s}</td>
                        <td>123</td>
                        <td>no 3D secure</td>
                    </tr>
                    <tr>
                        <td>{s name="cardType/creditCard"}{/s}</td>
                        <td>Visa</td>
                        <td>4711100000000000</td>
                        <td>{s name="column/expiryDateString"}{/s}</td>
                        <td>123</td>
                        <td>secret!33</td>
                    </tr>
                    <tr>
                        <td>{s name="cardType/debitCard"}{/s}</td>
                        <td>Visa Electron</td>
                        <td>4012888888881881</td>
                        <td>{s name="column/expiryDateString"}{/s}</td>
                        <td>123</td>
                        <td>no 3D secure</td>
                    </tr>
                    <tr>
                        <td>{s name="cardType/rejection"}{/s}</td>
                        <td>Visa</td>
                        <td>4644400000308888</td>
                        <td>{s name="column/expiryDateString"}{/s}</td>
                        <td>123</td>
                        <td>no 3D secure</td>
                    </tr>
                {/block}
            </table>
        {/block}

    </div>
{/block}
