<?php

/*
 * This file is part of the Alameda Smoke Test package.
 *
 * (c) Sebastian Kuhlmann <zebba@hotmail.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alameda\Quality\Formatter;

use Alameda\Quality\ClockResultList;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Outputs the result to the console as a JSON string
 *
 * @example
 * {
 *  "statistics": {
 *      "total_number": 279,
 *      "total_time": 0.180304,
 *      "average_time": 0,
 *      "variance": 0.0006037503060000003,
 *      "standard_Deviation": 0.024571330977380942
 *  },
 *  "services": {
 *      "successful": [
 *          {
 *              "id": "foo.service",
 *              "elapsed": 0.12345
 *          },
 *      ],
 *      "failed": [
 *          {
 *              "id": "foo.service",
 *              "message": "Some exception occured",
 *              "file": "\/path\/to\/a\/file.php",
 *              "line": 1,
 *              "trace": [
 *                  {
 *                      "file": "\/path\/to\/a\/file.php",
 *                      "line": 1,
 *                      "function": "foo",
 *                      "class": "Bar",
 *                      "type": "->",
 *                      "args": [
 *                          "Some exception occured",
 *                          {
 *                              "errorInfo": null
 *                          }
 *                      ]
 *                  },
 *                  ...
 *              ]
 *          },
 *      ],
 *      "inactive": [
 *          {
 *              "id": "request",
 *              "scope": "request"
 *          },
 *      ]
 *  }
 * }
 *
 * @author Sebastian Kuhlmann <zebba@hotmail.de>
 */
class JsonFormatter implements FormatterInterface
{
    /** @inheritdoc */
    public function displayResults(OutputInterface $output, ClockResultList $result)
    {
        $output->write(json_encode($result, JSON_PRETTY_PRINT));
    }
}
