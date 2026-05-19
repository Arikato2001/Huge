<div class="container">
    <h1>UserController/changeUserRole</h1>

    <!-- echo out the system feedback (error and success messages) -->
    <?php $this->renderFeedbackMessages(); ?>

    <div class="box">
        <h2>Change account type</h2>
        <p>
            This page is a basic implementation of the upgrade-process.
            User can click on that button to upgrade their accounts from
            "basic account" to "premium account". This script simple offers
            a click-able button that will upgrade/downgrade the account instantly.
            In a real world application you would implement something like a
            pay-process.
        </p>
        <p>
            Please note: This whole process has been renamed from AccountType (v3.0) to UserRole (v3.1).
        </p>

        <?php
        $currentRole = Session::get('user_account_type');
        $isAdmin = $currentRole == 7;

        function getRoleLabel($type)
        {
            switch ($type) {
                case 7:
                    return 'Admin';
                case 2:
                    return 'User';
                case 1:
                    return 'Gast';
                default:
                    return 'Unbekannt';
            }
        }
        ?>

        <h2>Currently your account type is: <?php echo getRoleLabel($currentRole); ?> (<?php echo $currentRole; ?>)</h2>
        <form action="<?php echo Config::get('URL'); ?>user/changeUserRole_action" method="post">
            <label for="user_account_type">Choose account type:</label>
            <select id="user_account_type" name="user_account_type"<?php echo !$isAdmin ? ' disabled="disabled"' : ''; ?>>
                <option value="1"<?php echo $currentRole == 1 ? ' selected' : ''; ?>>Gast</option>
                <option value="2"<?php echo $currentRole == 2 ? ' selected' : ''; ?>>User</option>
                <option value="7"<?php echo $currentRole == 7 ? ' selected' : ''; ?>>Admin</option>
            </select>

            <?php if ($isAdmin) { ?>
                <input type="submit" value="Aendern" />
            <?php } else { ?>
                <span>ReadOnly</span>
            <?php } ?>
        </form>
    </div>
</div>
