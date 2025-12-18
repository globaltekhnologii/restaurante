<?php
class TestRunner {
    protected $passed = 0;
    protected $failed = 0;
    protected $messages = [];

    protected function assertTrue($condition, $message = '') {
        if ($condition) {
            $this->passed++;
            // $this->messages[] = "✅ PASS: $message";
        } else {
            $this->failed++;
            $this->messages[] = "❌ FAIL: $message";
            echo "   ❌ FAIL: $message\n";
        }
    }

    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $this->passed++;
        } else {
            $this->failed++;
            $debug = "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true);
            $this->messages[] = "❌ FAIL: $message ($debug)";
            echo "   ❌ FAIL: $message ($debug)\n";
        }
    }

    public function getResults() {
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
?>
