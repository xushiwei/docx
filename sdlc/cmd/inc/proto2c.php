<?php
	function processMembers($members)
	{
		foreach ($members as $member)
		{
			$var = @$member->var;
			if ($var)
			{
				$type = $var->type;
				$name = $var->name;
				echo " (type: $type, name: $name)\n";
			}
			else
			{
				$switch = @$member->switch;
				if ($switch)
				{
					$expr = $switch->expr;
					echo "  switch: $expr\n";
					foreach ($switch->cases as $case)
					{
						$cond = $case->condition;
						echo "    case: $cond\n";
						processMembers($case->members);
					}
				}
			}
		}
	}
	
	foreach ($doc->sentences as $sent)
	{
		$struct = @$sent->struct;
		if ($struct)
		{
			$name = $struct->name;
			echo "struct: $name\n";
			processMembers($struct->members);
		}
		else
		{
			//todo
		}
	}
?>
