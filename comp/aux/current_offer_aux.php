<?php
// ###### Current Offer Auxilary ######
class Baggages {
    public $sli_id;    // set all: private
    public $seg_ids;   
    public $pass_ids;  
    public $baggages; 

    public function __construct($sli_id, $seg_ids, $pass_ids, $baggages) {
        $this->sli_id = $sli_id;
        $this->seg_ids = $seg_ids;
        $this->pass_ids = array_unique($pass_ids);
        $this->baggages = $baggages;
    }

    /**
     * Print baggage html after Offer
     * request. Upon printing an Offer,
     * (normal offer only) this function 
     * will print the text bellow 
     * the price button.
     * 
     * Args:
     *  $single_offer -> Same principle as
     *  Offer's print_html() if $single_offer = 1,
     *  then print in current offer tab otherwise
     *  print in flight results.
     */
    public function print_baggage_html($single_offer) {
        $total_seg = count($this->seg_ids);
        $total_pas = count($this->pass_ids);
        $total_bag = count($this->baggages);

        $total_bag_types = array();
        $total_bag_quans = array();

        if ($total_bag / $total_pas === $total_seg) {
            foreach ($this->baggages as $_ => $baggage) {
                $baggage_types = $baggage->get_types();
                $baggage_quans = $baggage->get_quans();
                array_push($total_bag_types, $baggage_types);
                array_push($total_bag_quans, $baggage_quans);
            }
        } else {
            console_log(' [*] Debug: $total_bag->{'.$total_bag.'} $total_pas->{'.$total_pas.'} $total_seg->{'.$total_seg. '} $total_bag / $total_pas === $total_seg');
            throw new Exception('\t- Number of bags per passenger should be equal to the number of segments');
        }

        if ($single_offer) {
            $code = 'document.getElementById("curr-bags_text").innerHTML = "'; 
            $msg = '';
        } else {
            $code = '<div id=\'baggage_text\'>';
            $msg = 'Includes ';
        }

        if (count(array_unique($total_bag_quans)) === 1 && 1 === count(array_unique($total_bag_types))) {
            $index = 0;
            while ($index < count($total_bag_quans[0])) {
                if ($index > 0 && $msg !== '' && $msg !== 'Includes ') {
                    $msg = $msg . ' and ';
                }
                $type = str_replace('_', ' ', $total_bag_types[0][$index]);
                $quan = $total_bag_quans[0][$index];
                $msg = $msg . $quan . ' ' . $type . ' bag';
                if (intval($quan) > 1) {
                    $msg = $msg . 's';
                }
                $index++;
            }
        } else if (count(array_unique($total_bag_quans)) > 1) {
            throw new Exception('\t- Passengers have different baggage allocations');
        }

        if ($single_offer && $msg === '') {
            console_log('\t- Bags per passenger: 0');
            return 'document.getElementById("entry-bag").style.display = "none"; ';
        } else if ($msg === 'Includes ') {
            return;
        }

        if ($single_offer) {
            console_log('\t- Bags per passenger: {'.$msg.'}');
            return $code . $msg . ' per passenger."; ';
        } else {
            return $code . $msg . '</div>';
        }
        return;
    }
}

class Baggage {
    public $pass_id;
    public $types;
    public $quans;

    public function __construct($pass_id, $types, $quans){
        $this->pass_id = $pass_id;
        $this->types = $types;
        $this->quans = $quans;
    }

    public function get_pass_id() 
    {
        return $this->pass_id;
    }

    public function get_types()
    {
        return $this->types;
    }

    public function get_quans()
    {
        return $this->quans;
    }
}
?>