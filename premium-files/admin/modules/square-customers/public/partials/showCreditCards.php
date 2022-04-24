<h2>Square saved credit cards</h2>

<table id="square-credit-cards" style="width: 100%;text-align: left;">
    <!-- This is for adding credit card... -->
    <?php if(@$userSquare['customer']['cards']): ?>
        <thead>
        <tr>
            <th>Brand</th>
            <th>Last four</th>
            <th>Exp. date</th>
            <th>Actions</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($userSquare['customer']['cards'] as $card): ?>
            <tr>
                <td><?php echo $card['card_brand'] ?></td>
                <td><?php echo $card['last_4'] ?></td>
                <td><?php echo $card['exp_month'] ?>/<?php echo $card['exp_year'] ?></td>
                <td>
                    <a href="?user_id=<?php echo $user->ID ?>&action=deleteCreditCard&cardId=<?php echo $card['id'] ?>" class="button deleteCreditCard">Delete card</a>
                </td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php else: ?>
        <p>You don't have saved credit cards.</p>
    <?php endif; ?>
</table>
<br>
<a href="#addCard" class="button button-primary button-add-card">ADD CARD</a>
<br><br>
<div class="addCardsWrapper">
    <h2>Square add credit card</h2>
    <div class="addCardErrors"></div>
    <form method="POST" id="addCardForm">
        <input type="hidden" id="card-nonce" name="card-nonce">
        <input type="hidden" class="buyerVerification-token" name="buyerVerification_token"  />
        <?php wp_nonce_field( 'squareAddCreditCard') ?>

        <table class="squareAddCard">
            <tbody>
            <tr>
                <th>
                    <label>Credit card number:</label>
                </th>
                <td>
                    <iframe id="sq-card-number" name="sq-card-number" class="sq-input" frameborder="0" width="100%" scrolling="no" height="18" src="https://connect.squareup.com/v2/iframe?type=cardNumber&amp;app_id=sq0idp-PuT24-tB2udlN7xoWNq_aA"></iframe>
                </td>
            </tr>
            <tr>
                <th>
                    <label>Exp. date:</label>
                </th>
                <td>
                    <iframe id="sq-expiration-date" name="sq-expiration-date" class="sq-input" frameborder="0" width="100%" scrolling="no" height="18" src="https://connect.squareup.com/v2/iframe?type=expirationDate"></iframe>
                </td>
            </tr>
            <tr>
                <th>
                    <label>CCV:</label>
                </th>
                <td>
                    <iframe id="sq-cvv" name="sq-cvv" class="sq-input" frameborder="0" width="100%" scrolling="no" height="18" src="https://connect.squareup.com/v2/iframe?type=cvv"></iframe>
                </td>
            </tr>
            <tr>
                <th>
                    <label>Postal code:</label>
                </th>
                <td>
                    <iframe id="sq-postal-code" name="sq-postal-code" class="sq-input" frameborder="0" width="100%" scrolling="no" height="18" src="https://connect.squareup.com/v2/iframe?type=postalCode"></iframe>
                </td>
            </tr>
            <tr>
                <th>
                    <button id="sq-creditcard" class="button button-primary" onclick="requestCardNonce(event)">
                        Add Card
                    </button>
                </th>
                <th></th>
            </tr>
            </tbody>
        </table>
    </form>
</div>