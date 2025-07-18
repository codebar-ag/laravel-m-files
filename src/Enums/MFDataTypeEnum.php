<?php

namespace CodebarAg\MFiles\Enums;

enum MFDataTypeEnum: int
{
    case UNINITIALIZED = 0; // Document/Object
    case TEXT = 1; // Text
    case INTEGER = 2; // A 32-bit integer
    case FLOATING = 3; // A double-precision floating point
    case DATE = 5; // Date
    case TIME = 6; // Time
    case TIMESTAMP = 7; // Timestamp
    case BOOLEAN = 8; // Boolean
    case LOOKUP = 9; // Lookup (from a value list)
    case MULTISELECTLOOKUP = 10; // Multiple selection from a value list
    case INTEGER64 = 11; // A 64-bit integer
    case FILETIME = 12; // FILETIME (a 64-bit integer)
    case MULTILINETEXT = 13; // Multi-line text
    case ACL = 14; // The access control list (ACL)
}
