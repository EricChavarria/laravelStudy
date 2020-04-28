<?php



class Loan {

	public $id;
	public $principle;
	public $rate;
	public $months;
	public $monthly_payment;
	public $total_amount_paid;

	public function __construct(string $id, float $principle, float $rate, int $months) {
		$this->id = $id;
		$this->principle = $principle;
		$this->rate = $rate;
		$this->months = $months;
		$this->monthly_payment = $this->makeDouble($rate*$principle/(1-pow(1+$rate,-$months)), true);
		$this->total_amount_paid = 0.00;
	}

	public function makePayment(float $payment = null) : float {
		$payment = $payment ?? $this->monthly_payment;

		$interest_paid = $this->getINP();

		$this->principle -= $payment - $interest_paid;

		$this->total_amount_paid += $payment;

		$refund = 0;

		if($this->principle < 0) {
			$refund = -$this->principle;
			$this->principle = 0;
		}

		return $refund;
	}

	public function makeExtraPayment(float $payment) {
		$this->principle -= $payment;

		$refund = 0;

		if($this->principle < 0) {
			$refund = -$this->principle;
			$this->principle = 0;
		}

		$this->total_amount_paid += $payment;

		return $refund;
	}

	private function makeDouble(float $num, bool $is_greedy) : float {
		$num *= 100;
		$num = $is_greedy ? ceil($num) : floor($num);
		$num /= 100;

		return $num;
	}

	public function getINP() : float {
		return $this->makeDouble($this->principle * $this->rate, false);
	}
	
	public function getMoneyRequiredToMatchINP(float $desiredINP) : float {
		return $this->principle - ($desiredINP / $this->rate);
	}

	public function getMoneyToEvenINP(float $extra, float $sumOfInverseRates) : float {
		return $extra / ($this->rate * $sumOfInverseRates);
	}

}

$loans_req = json_decode(file_get_contents('php://input'), true)['loans'];
$extra = (float) json_decode(file_get_contents('php://input'), true)['extra'];
$loans = [];
foreach($loans_req as $loan) {
	$loans[] = new Loan($loan['id'], $loan['principle'], (float) $loan['rate'], 12);
}

//var_dump($loans);

//$loan1 = new Loan("Loan1", 1000, .10, 12);
//$loan2 = new Loan("Loan2", 2000, .05, 12);
//$loan3 = new Loan("Loan3", 200, .05, 12);
//$loan4 = new Loan("Loan4", 100, .05, 12);
//$loan5 = new Loan("Loan5", 10, .01, 12);
//
////var_dump($loan1,$loan2,$loan3,$loan4,$loan5);
//var_dump($loan1->getINP()." ".$loan1->id,$loan2->getINP()." ".$loan2->id,$loan3->getINP()." ".$loan3->id,$loan4->getINP()." ".$loan4->id,$loan5->getINP()." ".$loan5->id);
//
//$loans = [$loan1, $loan2, $loan3, $loan4];
$INPs = [];


do {
	usort($loans, function ($a, $b) {
		$value = $b->getINP() - $a->getINP();

		if($value === 0) return 0;
		if($value > 0) return 1;
		return -1;
	});

	$topINPLoans = [];
	$bottomINPLoans  = $loans;

	foreach($loans as $loan) {
		if(sizeof($topINPLoans) === 0 || $loan->getINP() === $topINPLoans[0]->getINP()) {
			$topINPLoans[] = array_shift($bottomINPLoans);
		}
	}


	$extraMoneyToMatchNextINP = [];

	foreach($topINPLoans as $loan) {
		$extraMoneyToMatchNextINP[] = sizeof($bottomINPLoans) > 0 ? $loan->getMoneyRequiredToMatchINP($bottomINPLoans[0]->getINP()) : 0;
	}


	$sumOfExtra = array_sum($extraMoneyToMatchNextINP);

//	var_dump("BEFORE IF============",$extra, $sumOfExtra, sizeof($bottomINPLoans),$extra < $sumOfExtra,"\/ \/ \/ ",$topINPLoans);

	if(sizeof($bottomINPLoans) === 0 || $extra <= $sumOfExtra) {

		$sumOfInverseRates = 0;
		foreach($topINPLoans as $loan) {
			$sumOfInverseRates += 1 / $loan->rate;
		}

		$payments = [];
		foreach($topINPLoans as $loan) {
			$toPay = $loan->getMoneyToEvenINP($extra, $sumOfInverseRates);
			$payments[] = $toPay;
			$loan->makeExtraPayment($toPay);
		}

		$nextINP = [];
		foreach($topINPLoans as $loan) {
			$nextINP[] = $loan->getINP();
		}

//		var_dump("IF BLOCK==============",$payments, array_sum($payments), $extra, $nextINP);

		$extra -= array_sum($payments);
	} else {
//		var_dump("ELSE BLOCK==========", $sumOfExtra);
		foreach($topINPLoans as $loan) {
			$loan->makeExtraPayment(array_shift($extraMoneyToMatchNextINP));
		}
		$extra -= $sumOfExtra;
	}
} while ($extra > 0);

//var_dump("END=================");
//var_dump($loan1,$loan2,$loan3,$loan4,$loan5);
//var_dump($loan1->getINP()." ".$loan1->id,$loan2->getINP()." ".$loan2->id,$loan3->getINP()." ".$loan3->id,$loan4->getINP()." ".$loan4->id,$loan5->getINP()." ".$loan5->id);

foreach($loans as $loan) {
	echo ("For the ".$loan->id." loan pay $".$loan->total_amount_paid." this month.\n");
}
