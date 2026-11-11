<?php
namespace App\Console\Commands;
use App\Services\TicketEmailStatus;
use Illuminate\Console\Command;
class ImportTicketBounces extends Command {
    protected $signature='tickets:import-bounces {--file= : Read a saved .eml file (use - for standard input)}';
    protected $description='Import correlated delivery failures from a trusted bounce inbox or saved DSN';
    public function handle(TicketEmailStatus $status): int {
        if ($file=$this->option('file')) {
            $stream=@fopen($file==='-'?'php://stdin':$file,'rb');
            if (!$stream) { $this->error('Cannot open bounce input.'); return 1; }
            $raw=stream_get_contents($stream,2097153); fclose($stream);
            $this->info('Matched failures: '.$status->ingest($raw)); return 0;
        }
        if (!config('ticket_email.bounce_mailbox')) { $this->line('Bounce inbox is not configured.'); return 0; }
        if (!function_exists('imap_open')) { $this->error('Enable the PHP IMAP extension to monitor the bounce inbox.'); return 1; }
        imap_timeout(IMAP_OPENTIMEOUT,15); imap_timeout(IMAP_READTIMEOUT,30);
        $inbox=@imap_open(config('ticket_email.bounce_mailbox'),config('ticket_email.bounce_username'),config('ticket_email.bounce_password'),OP_READONLY,1);
        if (!$inbox) { $this->error('Cannot connect to bounce inbox. Check server settings and credentials.'); return 1; }
        $count=0;
        try {
            // Scan read and unread messages; retain messages unchanged, and deduplicate in the database.
            foreach (imap_search($inbox,'SINCE "'.date('d-M-Y',strtotime('-30 days')).'"',SE_UID) ?: [] as $uid) {
                $overview=imap_fetch_overview($inbox,(string)$uid,FT_UID);
                if (!$overview || $overview[0]->size>2097152) continue;
                $raw=imap_fetchheader($inbox,$uid,FT_UID)."\r\n".imap_body($inbox,$uid,FT_UID|FT_PEEK);
                $count+=$status->ingest($raw);
            }
        } finally { imap_close($inbox); }
        $this->info('Matched failures: '.$count); return 0;
    }
}
