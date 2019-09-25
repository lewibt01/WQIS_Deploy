<?php
    /**
     * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
     * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
     *
     * Licensed under The MIT License
     * For full copyright and license information, please see the LICENSE.txt
     * Redistributions of files must retain the above copyright notice.
     *
     * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
     * @link          https://cakephp.org CakePHP(tm) Project
     * @since         0.10.0
     * @license       https://opensource.org/licenses/mit-license.php MIT License
     */
    $cakeDescription = 'CakePHP: the rapid development php framework';
?>
<!DOCTYPE html>
<html>
    <head>
        <?= $this->Html->charset() ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            Water Quality Information Service
        </title>
        <?= $this->Html->meta('icon') ?>

        <?= $this->fetch('meta') ?>
        <?= $this->fetch('css') ?>
        <?= $this->fetch('script') ?>

        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
        <?= $this->Html->css('bootstrap-glyphicons.min.css') ?>
        <!-- JQuery JS import -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <?= $this->Html->script('jquery.msgbox.min.js') ?>
        <!-- Project css -->
        <?= $this->Html->css('styling.css') ?>

        <?= $this->Html->css('cakemessages.css') ?>
        <?php //$this->Html->css('base.css') ?>
        <?php //$this->Html->css('cake.css') ?>
        <?= $this->Html->script('ajaxlooks.js') ?>


    </head>
    <body class="h-100">
        <nav class="navbar fixed-top navbar-expand-lg navbar-light mb-8">
            <?= $this->Html->link(__('WQIS'), ['controller' => 'users', 'action' => 'login'], ['class' => 'navbar-brand']); ?>
            <?php if ($userinfo !== NULL) { ?>

                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".navbar-collapse" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse">
                        <ul class="navbar-nav mr-auto navbar-right">
                            <li class="nav-item">
								<?php
								if ($this->name == "SiteLocations") {
									echo "<a href=\"../site-locations/chartselection\" class=\"nav-link active\">View Water Quality Data</a>";
								}
								else {
									echo "<a href=\"../site-locations/chartselection\" class=\"nav-link\">View Water Quality Data</a>";
								}
								?>
                            </li>
                            <?php if ($admin) { ?>
                                <li class="nav-item">
									<?php
									if ($this->name == "Pages") {
										//strictly, probably should have a separate value for admin. This organization is fucked, will deal with it later
										echo "<a href=\"../pages/administratorpanel\" class=\"nav-link active\">Admin Panel</a>";
									}
									else {
										echo "<a href=\"../pages/administratorpanel\" class=\"nav-link\">Admin Panel</a>";
									}
									?>
                                </li>
                                <?php
                            }
                            ?>
                            <li class="nav-item">
								<?php
								if ($this->name == "Users") {
									echo "<a href=\"../users/edituserinfo\" class=\"nav-link active\">User Profile</a>";
								}
								else {
									echo "<a href=\"../users/edituserinfo\" class=\"nav-link\">User Profile</a>";
								}
								?>
                            </li>
                            <?php if ($admin) { ?>
                            <li class="nav-item">
								<?php
								if ($this->name == "Feedback") {
									echo "<a href=\"../feedback/adminfeedback\" class=\"nav-link active\">Feedback</a>";
								}
								else {
									echo "<a href=\"../feedback/adminfeedback\" class=\"nav-link\">Feedback</a>";
								}
								?>
                            </li>
                            <?php 
                            } 
                            else { ?>
                            <li class="nav-item">
                                <?php
								if ($this->name == "Feedback") {
									echo "<a href=\"../feedback/userfeedback\" class=\"nav-link active\">Feedback</a>";
								}
								else {
									echo "<a href=\"../feedback/userfeedback\" class=\"nav-link\">Feedback</a>";
								}
								?>
                            </li>
                            <?php
                            }
                            ?>
                        </ul>

                        <input class="btn btn-outline-primary" type='button' value='Logout' onclick="location.href = '<?php echo $this->Url->build(['controller' => 'users', 'action' => 'logout']); ?>';" />
                    <?php
                }
            ?>
        </nav>
        <br>
        <br>
        <br>
        <br>
        <?= $this->Flash->render() ?>
        <div class="container clearfix">
            <?= $this->fetch('content') ?>
        </div>
        <footer>
        </footer>
    </body>
</html>
