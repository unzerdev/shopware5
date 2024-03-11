{namespace name="frontend/unzer_payment/frames/test_data/installment_secured"}

{block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_test_data"}
    <div id="unzer-payment--installment-secured-test-data">
        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_test_data_title"}
            <p>{s name='title'}{/s}</p>
        {/block}
        {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_test_data_table"}
            <table class="unzer-payment--test-data-table">
                {block name="frontend_checkout_confirm_unzer_payment_frames_paylater_installment_test_data_table_rows"}
                    <tr>
                        <th><p>{s name='column/accountHolder'}{/s}</p></th>
                        <th><p>{s name='column/iban'}{/s}</p></th>
                        <th><p>{s name='column/birthday'}{/s}</p></th>
                    </tr>
                    <tr>
                        <td>Manuel Weißmann</td>
                        <td>DE89370400440532013000</td>
                        <td>03.10.1979</td>
                    </tr>
                {/block}
            </table>
        {/block}
    </div>
{/block}
