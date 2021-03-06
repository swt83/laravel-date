<?php

namespace Travis;

class Date
{
    /**
     * Object timestamp.
     *
     * @var     int
     */
    protected $time;

    /**
     * Forge the date object.
     *
     * @param   string  $str
     * @return  object
     */
    public static function forge($str = null)
    {
        $class = __CLASS__;
        return new $class($str);
    }

    /**
     * Alias of forge().
     *
     * @param   string  $str
     * @return  object
     */
    public static function make($str = null)
    {
        return static::forge($str);
    }

    /**
     * Forge the date object.
     *
     * @param   string  $str
     * @return  object
     */
    public function __construct($str = null)
    {
        // if no given...
        if ($str === null)
        {
            // use now
            $this->time = time();
        }

        // if given...
        else
        {
            // if date object...
            if (static::is_object($str))
            {
                $this->time = $str->time();
            }

            // else...
            else
            {
                // else if number...
                if (is_numeric($str))
                {
                    // treat as unix time
                    $this->time = $str;
                }

                // finally...
                else
                {
                    // treat as string
                    $time = strtotime($str);

                    // if conversion fails...
                    if (!$time)
                    {
                        // set time as false
                        $this->time = false;
                    }
                    else
                    {
                        // accept time value
                        $this->time = $time;
                    }
                }
            }
        }
    }

    /**
     * Return the object timestamp.
     *
     * @return  int
     */
    public function time()
    {
        return $this->time;
    }

    /**
     * Return the current date value in desired format.
     *
     * @param   string  $str
     * @return  string
     */
    public function format($str)
    {
        // if valid unix timestamp...
        if ($this->time !== false)
        {
            // if on windows...
            if (static::win32_detect())
            {
                // return win32 formatted value
                return static::win32_strftime($str, $this->time);
            }

            // else if NOT windows...
            else
            {
                // return formatted value
                return strftime($str, $this->time);
            }
        }
        else
        {
            // return false
            return false;
        }
    }

    /**
     * Reforge the current date object.
     *
     * @param   string  $str
     * @param   boolean $is_new
     * @return  object
     */
    public function reforge($str, $is_new = false)
    {
        // catch error...
        if (!$this->time)
        {
            trigger_error('Cannot reforge an invalidate date.');
        }

        // amend the time
        $time = strtotime($str, $this->time);

        // if conversion fails...
        if (!$time)
        {
            // set time as false
            $new_time = false;
        }
        else
        {
            // accept time value
            $new_time = $time;
        }

        // if is new...
        if ($is_new)
        {
            // clone
            $new = clone $this;

            // set new time
            $new->time = $new_time;

            // return
            return $new;
        }

        // else if NOT new...
        else
        {
            // set new time
            $this->time = $new_time;
        }

        // return
        return $this;
    }

    /**
     * Alias of reforge().
     *
     * @param   string  $str
     * @param   boolean $is_new
     * @return  object
     */
    public function remake($str, $is_new = false)
    {
        return $this->reforge($str, $is_new);
    }

    /**
     * Return string of ago value based on current date and time.
     *
     * @return  string
     */
    public function ago()
    {
        // set now and then
        $now = time();
        $time = $this->time();

        // catch error
        if (!$time) return false;

        // build period and length arrays
        $periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
        $lengths = array(60, 60, 24, 7, 4.35, 12, 10);

        // get difference
        $difference = $now - $time;

        // set descriptor
        if ($difference < 0)
        {
            $difference = abs($difference); // absolute value
            $negative = true;
        }

        // do math
        for($j = 0; $difference >= $lengths[$j] and $j < count($lengths)-1; $j++)
        {
            $difference /= $lengths[$j];
        }

        // round difference
        $difference = intval(round($difference));

        // determine plural
        if($difference !== 1)
        {
            $periods[$j] .= 's';
        }

        // return
        return number_format($difference).' '.$periods[$j].' '.(isset($negative) ? '' : 'ago');
    }

    /**
     * Alias of ago().
     *
     * @return  string
     */
    public function until()
    {
        return $this->ago();
    }

    /**
     * Return date diff object comparing two dates.
     *
     * @param   mixed   $date1
     * @param   mixed   $date2
     * @return  object
     */
    public static function diff($date1, $date2 = null)
    {
        // convert to objects, all
        if (!static::is_object($date1)) $date1 = static::forge($date1);
        if (!static::is_object($date2)) $date2 = static::forge($date2);

        // catch error
        if (!$date1->time() or !$date2->time()) return false;

        // perform comparison
        $date1 = date_create($date1->format('%F %X'));
        $date2 = date_create($date2->format('%F %X'));
        $diff = date_diff($date1, $date2);

        // catch error
        if ($diff === false) return false;

        // return
        return $diff;
    }

    /**
     * Return number of days in month from given date.
     *
     * @param   mixed   $date
     * @return  int
     */
    public static function days_in_month($date)
    {
        // convert to object
        if (!static::is_object($date)) $date = static::forge($date);

        // return
        return cal_days_in_month(CAL_GREGORIAN, $date->format('%m'), $date->format('%Y'));
    }

    /**
     * Return HTML of a drawn calendar for month.
     *
     * @param   int         $month
     * @param   int         $year
     * @param   function    $closure
     * @return  string
     */
    public static function draw_calendar($month, $year, $closure = null)
    {
        // check for errors
        if (!is_numeric($month) or !is_numeric($year))
        {
            trigger_error('Invalid params for calendar method.');
        }

        // set today
        $today = static::forge();

        // set start and stop dates
        $start = static::forge($year.'-'.$month.'-01');
        $days_in_month = static::days_in_month($start);
        if ($start->format('%A') != 'Sunday') $start->reforge('previous sunday');
        $stop = static::forge($year.'-'.$month.'-'.$days_in_month);
        if ($stop->format('%A') != 'Saturday') $stop->reforge('next saturday');

        // build map
        $map = array();
        while ($start->time() <= $stop->time())
        {
            // add date to map
            $map[] = array(
                'date' => clone $start,
                'is_today' => $start->format('%F') == $today->format('%F') ? true : false,
                'is_disabled' => $start->format('%F') < $today->format('%F') ? true : false,
                'data' => $closure,
            );

            // increment
            $start->reforge('+1 day');
        }

        $html = '<table class="calendar">';
            $html .= '<thead>';
                $html .= '<tr>';
                    $html .= '<th>S</th>';
                    $html .= '<th>M</th>';
                    $html .= '<th>T</th>';
                    $html .= '<th>W</th>';
                    $html .= '<th>T</th>';
                    $html .= '<th>F</th>';
                    $html .= '<th>S</th>';
                $html .= '</tr>';
            $html .= '<thead>';
            $html .= '<tbody>';
                $count = 1;
                foreach ($map as $day)
                {
                    // open row
                    $html .= $count == 1 ? '<tr>' : '';

                    // load variables
                    $date = $day['date']; // object
                    $function = $day['data']; // function

                    // print information
                    $html .= '<td class="'.($day['is_disabled'] ? 'disabled ' : ($day['is_today'] ? 'today ' : '')).'">';
                        $html .= '<div class="date">'.$date->format('%e').'</div>';
                        $html .= is_callable($function) ? '<div class="data">'.$function($date).'</div>' : '';
                    $html .= '</td>';

                    // close row
                    $html .= $count == 7 ? '</tr>' : '';

                    // increment
                    $count = $count == 7 ? 1 : $count + 1;
                }
            $html .= '</tbody>';
        $html .= '</table>';

        // return
        return $html;
    }

    /**
     * Detect if server is Windows machine.
     *
     * @return  boolean
     */
    protected static function win32_detect()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Fix the strftime() function to work on win32 systems [CREDIT: mcpan68].
     *
     * @param   string  $format
     * @param   int     $time
     * @return  string
     */
    protected static function win32_strftime($format, $time = null)
    {
        // time
        if (!$time) $time = time();

        // This map is a work in progress.  It's a set of shortcuts to
        // get formats to work properly in Windows.  These are just the
        // cases that I've encountered.  Please contribute new shortcuts
        // as they work for you.

        // map
        $map = array(
            '%C' => sprintf('%02d', date('Y', $time) / 100),
            '%D' => '%m/%d/%y',
            '%e' => sprintf("%' 2d", date('j', $time)),
            '%h' => '%b',
            '%n' => '\n',
            '%r' => date('h:i:s', $time) . ' %p',
            '%R' => date('H:i', $time),
            '%t' => '\t',
            '%T' => '%H:%M:%S',
            '%u' => ($w = date('w', $time)) ? $w : 7,
            '%V' => static::win32_v($time),
            '%F' => '%Y-%m-%d',
        );

        // replace
        $format = str_replace(array_keys($map), array_values($map), $format);

        // return
        return strftime($format, $time);
    }

    /**
     * Fix the strftime %V value to work on win32 systems [CREDIT: mcpan68].
     *
     * @param   int     $time
     * @return  int
     */
    protected static function win32_v($time)
    {
        $year = strftime('%Y', $time);

        $first_day = strftime('%w', mktime(0, 0, 0, 1, 1, $year));
        $last_day = strftime('%w', mktime(0, 0, 0, 12, 31, $year));

        $number = $isonumber = strftime('%W', $time);

        if ($first_day === 1)
        {
            $isonumber--;
        }

        if ($first_day >= 1 and $first_day <= 4)
        {
            $isonumber++;
        }
        elseif ($number === 0)
        {
            $isonumber = win32_v(mktime(0, 0, 0, 12, 31, $year - 1));
        }

        if ($isonumber === 53 and ($last_day === 1 or $last_day === 2 or $last_day === 3))
        {
            $isonumber = 1;
        }

        // return
        return sprintf('%02d', $isonumber);
    }

    /**
     * Return object detection.
     *
     * @param   object  $object
     * @return  boolean
     */
    protected static function is_object($object)
    {
        if (!is_object($object))
        {
            return false;
        }

        $class = get_class($object);

        return $class === __CLASS__;
    }
}