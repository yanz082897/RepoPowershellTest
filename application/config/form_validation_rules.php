<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config = [
    'redeem' => [
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Amount',
            'label' => 'Transaction Amount',
            'rules' => 'required|trim|regex_match[/^(?![.0]*$)\d{1,18}+(?:\.\d{2})?$/]|max_length[12]',
            'errors' => [
                'regex_match' => "{field} must be a currency that is supported by Pinelabs API."
            ]
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Track2Data',
            'label' => 'Card Number',
            'rules' => 'required|trim|max_length[19]'
        ],
        [
            'field' => 'CardPin',
            'label' => 'Card Pin',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'InvoiceNumber',
            'label' => 'Invoice Number',
            'rules' => 'required|trim'
        ]
    ],
    'void' => [
        [
            'field' => 'PAN',
            'label' => 'PAN',
            'rules' => 'required|trim|max_length[19]'
        ],
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Amount',
            'label' => 'Transaction Amount',
            'rules' => 'required|trim|regex_match[/^(?![.0]*$)\d{1,18}+(?:\.\d{2})?$/]|max_length[12]',
            'errors' => [
                'regex_match' => "{field} must be a currency that is supported by Pinelabs API."
            ]
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Track2Data',
            'label' => 'Card Number',
            'rules' => 'required|trim|max_length[19]'
        ],
        [
            'field' => 'ReferenceNumber',
            'label' => 'Business Reference Number',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'InvoiceNumber',
            'label' => 'Original Invoice Number',
            'rules' => 'required|trim'
        ]
    ],
    'reverse' => [
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Amount',
            'label' => 'Transaction Amount',
            'rules' => 'required|trim|regex_match[/^(?![.0]*$)\d{1,18}+(?:\.\d{2})?$/]|max_length[12]',
            'errors' => [
                'regex_match' => "{field} must be a currency that is supported by Pinelabs API."
            ]
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Track2Data',
            'label' => 'Card Number',
            'rules' => 'required|trim|max_length[19]'
        ],
        // [
        //     'field' => 'CardPin',
        //     'label' => 'Card Pin',
        //     'rules' => 'required|trim|max_length[6]'
        // ],
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'InvoiceNumber',
            'label' => 'Invoice Number',
            'rules' => 'required|trim'
        ]
    ],
    'rev_void' => [
        [
            'field' => 'PAN',
            'label' => 'Card Number',
            'rules' => 'required|trim|max_length[19]'
        ],
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Amount',
            'label' => 'Transaction Amount',
            'rules' => 'required|trim|regex_match[/^(?![.0]*$)\d{1,18}+(?:\.\d{2})?$/]|max_length[12]',
            'errors' => [
                'regex_match' => "{field} must be a currency that is supported by Pinelabs API."
            ]
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'InvoiceNumber',
            'label' => 'Original Invoice Number',
            'rules' => 'required|trim'
        ]
    ],
    'balance' => [
        [
            'field' => 'Track2Data',
            'label' => 'Card Number',
            'rules' => 'required|trim|max_length[19]'
        ],
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ]
    ],
    'settlement' => [
        [
            'field' => 'TerminalID',
            'label' => 'Terminal Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'ProcessingCode',
            'label' => 'Processing Code',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'Stan',
            'label' => 'STAN',
            'rules' => 'required|trim|max_length[6]'
        ],
        [
            'field' => 'MerchantID',
            'label' => 'Merchant Identification',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'privateField[]',
            'label' => 'Private Field',
            'rules' => 'required|trim'
        ],
        [
            'field' => 'privateField[0][RedemptionCount]',
            'label' => 'Redemption Count',
            'rules' => 'required|trim|max_length[3]'
        ],
        [
            'field' => 'privateField[0][RedemptionAmount]',
            'label' => 'Redemption Amount',
            'rules' => 'required|trim|max_length[12]',
        ],
        [
            'field' => 'privateField[0][CancelRedeemCount]',
            'label' => 'Cancel Redemption Count',
            'rules' => 'required|trim|max_length[3]'
        ],
        [
            'field' => 'privateField[0][CancelRedeemAmount]',
            'label' => 'Cancel Redemption Amount',
            'rules' => 'required|trim|max_length[12]',
        ]
    ]

];
