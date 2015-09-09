<?php
function toggle_relay($gpio_relay){
        exec('gpio mode 0 out');
        exec('gpio mode 7 out');
        exec('gpio mode 8 out');
        exec('gpio mode 9 out');

        exec("gpio write $gpio_relay 0");
        sleep(2);
        exec("gpio write $gpio_relay 1");
}
?>
