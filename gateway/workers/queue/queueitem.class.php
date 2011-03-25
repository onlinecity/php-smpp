<?php
namespace gateway\workers\queue;

/**
 * Generic class for all queue items. Not much to see here
 * @author hd@onlinecity.dk
 */
abstract class QueueItem implements \Serializable
{
	const TYPE=0;
}