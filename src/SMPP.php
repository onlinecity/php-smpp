<?php


namespace smpp;

/**
 * Numerous constants for SMPP v3.4
 * Based on specification at: http://www.smsforum.net/SMPP_v3_4_Issue1_2.zip
 */
class SMPP
{
    // Command ids - SMPP v3.4 - 5.1.2.1 page 110-111
    const GENERIC_NACK = 0x80000000;
    const BIND_RECEIVER = 0x00000001;
    const BIND_RECEIVER_RESP = 0x80000001;
    const BIND_TRANSMITTER = 0x00000002;
    const BIND_TRANSMITTER_RESP = 0x80000002;
    const QUERY_SM = 0x00000003;
    const QUERY_SM_RESP = 0x80000003;
    const SUBMIT_SM = 0x00000004;
    const SUBMIT_SM_RESP = 0x80000004;
    const DELIVER_SM = 0x00000005;
    const DELIVER_SM_RESP = 0x80000005;
    const UNBIND = 0x00000006;
    const UNBIND_RESP = 0x80000006;
    const REPLACE_SM = 0x00000007;
    const REPLACE_SM_RESP = 0x80000007;
    const CANCEL_SM = 0x00000008;
    const CANCEL_SM_RESP = 0x80000008;
    const BIND_TRANSCEIVER = 0x00000009;
    const BIND_TRANSCEIVER_RESP = 0x80000009;
    const OUTBIND = 0x0000000B;
    const ENQUIRE_LINK = 0x00000015;
    const ENQUIRE_LINK_RESP = 0x80000015;

    //  Command status - SMPP v3.4 - 5.1.3 page 112-114
    const ESME_ROK = 0x00000000; // No Error
    const ESME_RINVMSGLEN = 0x00000001; // Message Length is invalid
    const ESME_RINVCMDLEN = 0x00000002; // Command Length is invalid
    const ESME_RINVCMDID = 0x00000003; // Invalid Command ID
    const ESME_RINVBNDSTS = 0x00000004; // Incorrect BIND Status for given command
    const ESME_RALYBND = 0x00000005; // ESME Already in Bound State
    const ESME_RINVPRTFLG = 0x00000006; // Invalid Priority Flag
    const ESME_RINVREGDLVFLG = 0x00000007; // Invalid Registered Delivery Flag
    const ESME_RSYSERR = 0x00000008; // System Error
    const ESME_RINVSRCADR = 0x0000000A; // Invalid Source Address
    const ESME_RINVDSTADR = 0x0000000B; // Invalid Dest Addr
    const ESME_RINVMSGID = 0x0000000C; // Message ID is invalid
    const ESME_RBINDFAIL = 0x0000000D; // Bind Failed
    const ESME_RINVPASWD = 0x0000000E; // Invalid Password
    const ESME_RINVSYSID = 0x0000000F; // Invalid System ID
    const ESME_RCANCELFAIL = 0x00000011; // Cancel SM Failed
    const ESME_RREPLACEFAIL = 0x00000013; // Replace SM Failed
    const ESME_RMSGQFUL = 0x00000014; // Message Queue Full
    const ESME_RINVSERTYP = 0x00000015; // Invalid Service Type
    const ESME_RINVNUMDESTS = 0x00000033; // Invalid number of destinations
    const ESME_RINVDLNAME = 0x00000034; // Invalid Distribution List name
    const ESME_RINVDESTFLAG = 0x00000040; // Destination flag (submit_multi)
    const ESME_RINVSUBREP = 0x00000042; // Invalid ‘submit with replace’ request (i.e. submit_sm with replace_if_present_flag set)
    const ESME_RINVESMSUBMIT = 0x00000043; // Invalid esm_SUBMIT field data
    const ESME_RCNTSUBDL = 0x00000044; // Cannot Submit to Distribution List
    const ESME_RSUBMITFAIL = 0x00000045; // submit_sm or submit_multi failed
    const ESME_RINVSRCTON = 0x00000048; // Invalid Source address TON
    const ESME_RINVSRCNPI = 0x00000049; // Invalid Source address NPI
    const ESME_RINVDSTTON = 0x00000050; // Invalid Destination address TON
    const ESME_RINVDSTNPI = 0x00000051; // Invalid Destination address NPI
    const ESME_RINVSYSTYP = 0x00000053; // Invalid system_type field
    const ESME_RINVREPFLAG = 0x00000054; // Invalid replace_if_present flag
    const ESME_RINVNUMMSGS = 0x00000055; // Invalid number of messages
    const ESME_RTHROTTLED = 0x00000058; // Throttling error (ESME has exceeded allowed message limits)
    const ESME_RINVSCHED = 0x00000061; // Invalid Scheduled Delivery Time
    const ESME_RINVEXPIRY = 0x00000062; // Invalid message (Expiry time)
    const ESME_RINVDFTMSGID = 0x00000063; // Predefined Message Invalid or Not Found
    const ESME_RX_T_APPN = 0x00000064; // ESME Receiver Temporary App Error Code
    const ESME_RX_P_APPN = 0x00000065; // ESME Receiver Permanent App Error Code
    const ESME_RX_R_APPN = 0x00000066; // ESME Receiver Reject Message Error Code
    const ESME_RQUERYFAIL = 0x00000067; // query_sm request failed
    const ESME_RINVOPTPARSTREAM = 0x000000C0; // Error in the optional part of the PDU Body.
    const ESME_ROPTPARNOTALLWD = 0x000000C1; // Optional Parameter not allowed
    const ESME_RINVPARLEN = 0x000000C2; // Invalid Parameter Length.
    const ESME_RMISSINGOPTPARAM = 0x000000C3; // Expected Optional Parameter missing
    const ESME_RINVOPTPARAMVAL = 0x000000C4; // Invalid Optional Parameter Value
    const ESME_RDELIVERYFAILURE = 0x000000FE; // Delivery Failure (data_sm_resp)
    const ESME_RUNKNOWNERR = 0x000000FF; // Unknown Error

    // SMPP v3.4 - 5.2.5 page 117
    const TON_UNKNOWN = 0x00;
    const TON_INTERNATIONAL = 0x01;
    const TON_NATIONAL = 0x02;
    const TON_NETWORKSPECIFIC = 0x03;
    const TON_SUBSCRIBERNUMBER = 0x04;
    const TON_ALPHANUMERIC = 0x05;
    const TON_ABBREVIATED = 0x06;

    // SMPP v3.4 - 5.2.6 page 118
    const NPI_UNKNOWN = 0x00;
    const NPI_E164 = 0x01;
    const NPI_DATA = 0x03;
    const NPI_TELEX = 0x04;
    const NPI_E212 = 0x06;
    const NPI_NATIONAL = 0x08;
    const NPI_PRIVATE = 0x09;
    const NPI_ERMES = 0x0a;
    const NPI_INTERNET = 0x0e;
    const NPI_WAPCLIENT = 0x12;

    // ESM bits 1-0 - SMPP v3.4 - 5.2.12 page 121-122
    const ESM_SUBMIT_MODE_DATAGRAM = 0x01;
    const ESM_SUBMIT_MODE_FORWARD = 0x02;
    const ESM_SUBMIT_MODE_STOREANDFORWARD = 0x03;
    // ESM bits 5-2
    const ESM_SUBMIT_BINARY = 0x04;
    const ESM_SUBMIT_TYPE_ESME_D_ACK = 0x08;
    const ESM_SUBMIT_TYPE_ESME_U_ACK = 0x10;
    const ESM_DELIVER_SMSC_RECEIPT = 0x04;
    const ESM_DELIVER_SME_ACK = 0x08;
    const ESM_DELIVER_U_ACK = 0x10;
    const ESM_DELIVER_CONV_ABORT = 0x18;
    const ESM_DELIVER_IDN = 0x20; // Intermediate delivery notification
    // ESM bits 7-6
    const ESM_UHDI = 0x40;
    const ESM_REPLYPATH = 0x80;

    // SMPP v3.4 - 5.2.17 page 124
    const REG_DELIVERY_NO = 0x00;
    const REG_DELIVERY_SMSC_BOTH = 0x01; // both success and failure
    const REG_DELIVERY_SMSC_FAILED = 0x02;
    const REG_DELIVERY_SME_D_ACK = 0x04;
    const REG_DELIVERY_SME_U_ACK = 0x08;
    const REG_DELIVERY_SME_BOTH = 0x10;
    const REG_DELIVERY_IDN = 0x16; // Intermediate notification

    // SMPP v3.4 - 5.2.18 page 125
    const REPLACE_NO = 0x00;
    const REPLACE_YES = 0x01;

    // SMPP v3.4 - 5.2.19 page 126
    const DATA_CODING_DEFAULT = 0;
    const DATA_CODING_IA5 = 1; // IA5 (CCITT T.50)/ASCII (ANSI X3.4)
    const DATA_CODING_BINARY_ALIAS = 2;
    const DATA_CODING_ISO8859_1 = 3; // Latin 1
    const DATA_CODING_BINARY = 4;
    const DATA_CODING_JIS = 5;
    const DATA_CODING_ISO8859_5 = 6; // Cyrllic
    const DATA_CODING_ISO8859_8 = 7; // Latin/Hebrew
    const DATA_CODING_UCS2 = 8; // UCS-2BE (Big Endian)
    const DATA_CODING_PICTOGRAM = 9;
    const DATA_CODING_ISO2022_JP = 10; // Music codes
    const DATA_CODING_KANJI = 13; // Extended Kanji JIS
    const DATA_CODING_KSC5601 = 14;

    // SMPP v3.4 - 5.2.25 page 129
    const DEST_FLAG_SME = 1;
    const DEST_FLAG_DISTLIST = 2;

    // SMPP v3.4 - 5.2.28 page 130
    const STATE_ENROUTE = 1;
    const STATE_DELIVERED = 2;
    const STATE_EXPIRED = 3;
    const STATE_DELETED = 4;
    const STATE_UNDELIVERABLE = 5;
    const STATE_ACCEPTED = 6;
    const STATE_UNKNOWN = 7;
    const STATE_REJECTED = 8;


    public static function getStatusMessage($statuscode)
    {
        switch ($statuscode) {
            case SMPP::ESME_ROK: return 'No Error';
            case SMPP::ESME_RINVMSGLEN: return 'Message Length is invalid';
            case SMPP::ESME_RINVCMDLEN: return 'Command Length is invalid';
            case SMPP::ESME_RINVCMDID: return 'Invalid Command ID';
            case SMPP::ESME_RINVBNDSTS: return 'Incorrect BIND Status for given command';
            case SMPP::ESME_RALYBND: return 'ESME Already in Bound State';
            case SMPP::ESME_RINVPRTFLG: return 'Invalid Priority Flag';
            case SMPP::ESME_RINVREGDLVFLG: return 'Invalid Registered Delivery Flag';
            case SMPP::ESME_RSYSERR: return 'System Error';
            case SMPP::ESME_RINVSRCADR: return 'Invalid Source Address';
            case SMPP::ESME_RINVDSTADR: return 'Invalid Dest Addr';
            case SMPP::ESME_RINVMSGID: return 'Message ID is invalid';
            case SMPP::ESME_RBINDFAIL: return 'Bind Failed';
            case SMPP::ESME_RINVPASWD: return 'Invalid Password';
            case SMPP::ESME_RINVSYSID: return 'Invalid System ID';
            case SMPP::ESME_RCANCELFAIL: return 'Cancel SM Failed';
            case SMPP::ESME_RREPLACEFAIL: return 'Replace SM Failed';
            case SMPP::ESME_RMSGQFUL: return 'Message Queue Full';
            case SMPP::ESME_RINVSERTYP: return 'Invalid Service Type';
            case SMPP::ESME_RINVNUMDESTS: return 'Invalid number of destinations';
            case SMPP::ESME_RINVDLNAME: return 'Invalid Distribution List name';
            case SMPP::ESME_RINVDESTFLAG: return 'Destination flag (submit_multi)';
            case SMPP::ESME_RINVSUBREP: return 'Invalid ‘submit with replace’ request (i.e. submit_sm with replace_if_present_flag set)';
            case SMPP::ESME_RINVESMSUBMIT: return 'Invalid esm_SUBMIT field data';
            case SMPP::ESME_RCNTSUBDL: return 'Cannot Submit to Distribution List';
            case SMPP::ESME_RSUBMITFAIL: return 'submit_sm or submit_multi failed';
            case SMPP::ESME_RINVSRCTON: return 'Invalid Source address TON';
            case SMPP::ESME_RINVSRCNPI: return 'Invalid Source address NPI';
            case SMPP::ESME_RINVDSTTON: return 'Invalid Destination address TON';
            case SMPP::ESME_RINVDSTNPI: return 'Invalid Destination address NPI';
            case SMPP::ESME_RINVSYSTYP: return 'Invalid system_type field';
            case SMPP::ESME_RINVREPFLAG: return 'Invalid replace_if_present flag';
            case SMPP::ESME_RINVNUMMSGS: return 'Invalid number of messages';
            case SMPP::ESME_RTHROTTLED: return 'Throttling error (ESME has exceeded allowed message limits)';
            case SMPP::ESME_RINVSCHED: return 'Invalid Scheduled Delivery Time';
            case SMPP::ESME_RINVEXPIRY: return 'Invalid message (Expiry time)';
            case SMPP::ESME_RINVDFTMSGID: return 'Predefined Message Invalid or Not Found';
            case SMPP::ESME_RX_T_APPN: return 'ESME Receiver Temporary App Error Code';
            case SMPP::ESME_RX_P_APPN: return 'ESME Receiver Permanent App Error Code';
            case SMPP::ESME_RX_R_APPN: return 'ESME Receiver Reject Message Error Code';
            case SMPP::ESME_RQUERYFAIL: return 'query_sm request failed';
            case SMPP::ESME_RINVOPTPARSTREAM: return 'Error in the optional part of the PDU Body.';
            case SMPP::ESME_ROPTPARNOTALLWD: return 'Optional Parameter not allowed';
            case SMPP::ESME_RINVPARLEN: return 'Invalid Parameter Length.';
            case SMPP::ESME_RMISSINGOPTPARAM: return 'Expected Optional Parameter missing';
            case SMPP::ESME_RINVOPTPARAMVAL: return 'Invalid Optional Parameter Value';
            case SMPP::ESME_RDELIVERYFAILURE: return 'Delivery Failure (data_sm_resp)';
            case SMPP::ESME_RUNKNOWNERR: return 'Unknown Error';
            default:
                return 'Unknown statuscode: '.dechex($statuscode);
        }
    }
}