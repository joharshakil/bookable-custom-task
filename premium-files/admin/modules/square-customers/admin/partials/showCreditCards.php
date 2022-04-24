<h2 class="form-table-white-heading">
    Square credit cards
    <a href="#addCard" class="button button-primary button-add-card">ADD CARD</a>
</h2>

<table class="form-table form-table-white" id="square-credit-cards">
    <!-- This is for adding credit card... -->
    <input type="hidden" id="card-nonce" name="card-nonce">
	<input type="hidden" class="buyerVerification-token" name="buyerVerification_token"  />
    <?php if($userSquare['customer']['cards']): ?>
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
                        <a href="?user_id=<?php echo $user->ID ?>&action=deleteCreditCard&cardId=<?php echo $card['id'] ?>" class="button deleteCreditCard" alt="Delete Card"></a>
                    </td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    <?php else: ?>
        <thead>
        <tr>
            <th>There is no saved credit cards with this customer.</th>
        </tr>
        </thead>
    <?php endif; ?>
</table>