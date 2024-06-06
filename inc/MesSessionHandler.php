<?php
class MesSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
	private $db;

	public function open($savePath, $sessionName): bool
	{
		$this->db  = sqlsrv_connect('(local)\sqlexpress', [
			'Database' => 'GEST_CANTINA'
		]);
		
		if( $this->db ) {
			return true;
		}
			
		echo( print_r( sqlsrv_errors(), true));
		throw new Exception ("Connessione inuuijnon trovata");
		return false;

	}

	public function close(): bool
	{
		sqlsrv_close($this->db);
		return true;
	}

	public function read($id): string | false
	{
		$stmt = sqlsrv_query(
			$this->db,
			"SELECT ses_Data, ses_LoggedIn FROM sessioni
			WHERE ses_Id = ?",
			[$id]
		);
		$riga = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

		if (!isset($riga)) {
			$stmt = sqlsrv_query(
				$this->db,
				"INSERT INTO sessioni(ses_Id, ses_Data, ses_LastImpress)
				VALUES(?,'a:0:{}', GETDATE())",
				[$id]
			);
			return 'a:0:{}';
		}
		if (intval($riga['ses_LoggedIn']) == 0) {
			return 'a:0:{}';
		}
		return ($riga['ses_Data']);
	}

	public function write($id, $data): bool
	{
		$stmt = sqlsrv_query(
			$this->db,
			"UPDATE sessioni SET
			ses_Data = ?
			WHERE ses_Id = ?",
			[$data, $id]
		);

		return $stmt ? true : false;
	}

	public function destroy($id): bool
	{
		$stmt = sqlsrv_query(
			$this->db,
			"DELETE FROM sessioni
			WHERE ses_Id = ?",
			[$id]
		);


		return true;
	}

	public function gc($maxlifetime): int | false
	{
		$now = new DateTime();
		$life = new DateInterval('PT' . $maxlifetime . 'S');

		$stmt = sqlsrv_query(
			$this->db,
			"DELETE FROM sessioni
			WHERE ses_LastImpress < ? OR ses_LastImpress IS NULL",
			[$now->sub($life)->format('Y-m-d\TH:i:s')]
		);

		return $stmt ? sqlsrv_num_rows($stmt) : false;
	}

	public function updateTimestamp(string $id, string $data): bool
	{
		return true;
	}

	public function validateId(string $id): bool
	{
		session_gc();
		$stmt = sqlsrv_query(
			$this->db,
			"SELECT ses_Data, ses_LoggedIn FROM sessioni
			WHERE ses_Id = ?",
			[$id]
		);
		$riga = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		return isset($riga) ? true : false;
	}
}
